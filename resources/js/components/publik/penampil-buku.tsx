import { BookOpen, ChevronFirst, ChevronLast, ChevronLeft, ChevronRight, Loader2, Maximize, Minimize, TriangleAlert } from 'lucide-react';
import { motion, useReducedMotion } from 'motion/react';
import type { PDFDocumentProxy } from 'pdfjs-dist';
import type { CSSProperties, KeyboardEvent as KeyboardEventReact, PointerEvent as PointerEventReact } from 'react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

/**
 * Penampil PDF mode flipbook ("buka buku"). Halaman dirender PDF.js (worker dari paket yang sama,
 * permintaan Range 206) menjadi gambar, lalu dibalik dengan animasi 3D Motion.
 * - Layar lebar: dua halaman berdampingan (halaman 1 = sampul di kanan).
 * - Layar sempit: satu halaman.
 * Kontrol: tombol, klik sisi halaman, geser (swipe), panah keyboard, Home/End, slider halaman,
 * layar penuh. Menghormati prefers-reduced-motion (tanpa animasi balik).
 * Komponen khusus browser: muat melalui lazy + KlienSaja.
 */

type Arah = 'maju' | 'mundur';
type Sebaran = { kiri: number | null; kanan: number | null };

const KUNCI_TRANSISI = [0.645, 0.045, 0.355, 1] as const;

function useDokumen(url: string) {
    const [doc, setDoc] = useState<PDFDocumentProxy | null>(null);
    const [rasio, setRasio] = useState(0.707);
    const [galat, setGalat] = useState<string | null>(null);

    useEffect(() => {
        let batal = false;
        let tugas: { destroy: () => Promise<void> } | null = null;
        void (async () => {
            try {
                const pdfjs = await import('pdfjs-dist');
                const worker = (await import('pdfjs-dist/build/pdf.worker.min.mjs?url')).default;
                pdfjs.GlobalWorkerOptions.workerSrc = worker;
                const t = pdfjs.getDocument({ url, rangeChunkSize: 262144, disableAutoFetch: true, disableStream: true });
                tugas = t;
                const d = await t.promise;
                const pertama = await d.getPage(1);
                const vp = pertama.getViewport({ scale: 1 });
                if (!batal) {
                    setRasio(vp.width / vp.height);
                    setDoc(d);
                }
            } catch (e) {
                if (!batal) {
                    setGalat(e instanceof Error ? e.message : 'Dokumen tidak dapat dimuat.');
                }
            }
        })();

        return () => {
            batal = true;
            void tugas?.destroy();
        };
    }, [url]);

    return { doc, rasio, galat };
}

/** Render satu halaman PDF ke URL gambar (blob) berlatar putih. */
async function renderHalaman(doc: PDFDocumentProxy, nomor: number, lebarPx: number): Promise<string> {
    const page = await doc.getPage(nomor);
    const dasar = page.getViewport({ scale: 1 });
    const viewport = page.getViewport({ scale: lebarPx / dasar.width });
    const kanvas = document.createElement('canvas');
    kanvas.width = Math.ceil(viewport.width);
    kanvas.height = Math.ceil(viewport.height);
    await page.render({ canvas: kanvas, viewport, background: '#ffffff' }).promise;
    const blob = await new Promise<Blob | null>((selesai) => kanvas.toBlob(selesai, 'image/jpeg', 0.9));

    return blob ? URL.createObjectURL(blob) : kanvas.toDataURL('image/jpeg', 0.9);
}

function Kertas({
    nomor,
    gambar,
    sisi,
    className,
    style,
}: {
    nomor: number | null;
    gambar?: string;
    sisi: 'kiri' | 'kanan' | 'tunggal';
    className?: string;
    style?: CSSProperties;
}) {
    if (nomor === null) {
        return <div className={cn('absolute inset-0', className)} style={style} aria-hidden />;
    }

    return (
        <div
            className={cn(
                'absolute inset-0 overflow-hidden bg-white',
                sisi === 'kiri' ? 'rounded-l-md' : sisi === 'kanan' ? 'rounded-r-md' : 'rounded-md',
                className,
            )}
            style={style}
        >
            {gambar ? (
                <img src={gambar} alt={`Halaman ${nomor}`} className="size-full object-fill select-none" draggable={false} />
            ) : (
                <div className="flex size-full items-center justify-center text-zinc-400">
                    <Loader2 className="size-6 animate-spin" aria-hidden />
                    <span className="sr-only">Memuat halaman {nomor}</span>
                </div>
            )}
            {/* Bayangan lipatan punggung buku */}
            {sisi === 'kiri' && (
                <div className="pointer-events-none absolute inset-y-0 right-0 w-10 bg-gradient-to-l from-black/20 to-transparent" aria-hidden />
            )}
            {sisi === 'kanan' && (
                <div className="pointer-events-none absolute inset-y-0 left-0 w-10 bg-gradient-to-r from-black/20 to-transparent" aria-hidden />
            )}
            <span className={cn('pointer-events-none absolute bottom-1.5 text-[10px] text-zinc-400', sisi === 'kiri' ? 'left-3' : 'right-3')} aria-hidden>
                {nomor}
            </span>
        </div>
    );
}

export default function PenampilBuku({ url, judul }: { url: string; judul: string }) {
    const { doc, rasio, galat } = useDokumen(url);
    const kurangiGerak = useReducedMotion();
    const wadah = useRef<HTMLDivElement>(null);
    const layar = useRef<HTMLDivElement>(null);
    const [lebarWadah, setLebarWadah] = useState(0);
    const [tinggiLayar, setTinggiLayar] = useState(800);
    const [layarPenuh, setLayarPenuh] = useState(false);
    const [halamanAcuan, setHalamanAcuan] = useState(1);
    const [balik, setBalik] = useState<Arah | null>(null);
    const [gambar, setGambar] = useState<Record<string, string>>({});
    const diminta = useRef(new Set<string>());
    const semuaUrl = useRef<string[]>([]);
    const geser = useRef<{ x: number; y: number } | null>(null);

    const total = doc?.numPages ?? 0;

    // Ukuran wadah & layar.
    useEffect(() => {
        const el = wadah.current;
        if (!el) {
            return;
        }
        const ukur = () => {
            setLebarWadah(el.clientWidth);
            setTinggiLayar(window.innerHeight);
        };
        ukur();
        const pengamat = new ResizeObserver(ukur);
        pengamat.observe(el);
        window.addEventListener('resize', ukur);
        const ubahPenuh = () => {
            setLayarPenuh(document.fullscreenElement === layar.current);
            ukur();
        };
        document.addEventListener('fullscreenchange', ubahPenuh);

        return () => {
            pengamat.disconnect();
            window.removeEventListener('resize', ukur);
            document.removeEventListener('fullscreenchange', ubahPenuh);
        };
    }, []);

    // Bersihkan URL gambar saat komponen dilepas.
    useEffect(() => () => semuaUrl.current.forEach((u) => URL.revokeObjectURL(u)), []);

    const ganda = lebarWadah >= 720;
    const tinggiMaks = Math.max(320, tinggiLayar * (layarPenuh ? 0.86 : 0.74));
    const lebarHalaman = Math.max(140, Math.floor(Math.min(ganda ? (lebarWadah - 24) / 2 : lebarWadah, tinggiMaks * rasio)));
    const tinggiHalaman = Math.floor(lebarHalaman / rasio);
    const lebarPx = Math.max(400, Math.round((lebarHalaman * Math.min(2, typeof window === 'undefined' ? 1 : window.devicePixelRatio || 1)) / 100) * 100);

    const sebaran = useMemo<Sebaran[]>(() => {
        if (!total) {
            return [];
        }
        if (!ganda) {
            return Array.from({ length: total }, (_, i) => ({ kiri: null, kanan: i + 1 }));
        }
        const s: Sebaran[] = [{ kiri: null, kanan: 1 }];
        for (let p = 2; p <= total; p += 2) {
            s.push({ kiri: p, kanan: p + 1 <= total ? p + 1 : null });
        }

        return s;
    }, [total, ganda]);

    const indeks = Math.max(
        0,
        sebaran.findIndex((s) => s.kiri === halamanAcuan || s.kanan === halamanAcuan),
    );
    const kini = sebaran[indeks];
    const berikut = sebaran[indeks + 1];
    const sebelum = sebaran[indeks - 1];

    const minta = useCallback(
        (nomor: number | null | undefined) => {
            if (!doc || !nomor || nomor < 1 || nomor > total) {
                return;
            }
            const kunci = `${nomor}@${lebarPx}`;
            if (diminta.current.has(kunci)) {
                return;
            }
            diminta.current.add(kunci);
            renderHalaman(doc, nomor, lebarPx)
                .then((u) => {
                    semuaUrl.current.push(u);
                    setGambar((g) => ({ ...g, [kunci]: u }));
                })
                .catch(() => diminta.current.delete(kunci));
        },
        [doc, total, lebarPx],
    );

    // Render halaman yang tampil dan pramuat sebaran sekitarnya.
    useEffect(() => {
        for (let i = indeks - 1; i <= indeks + 2; i++) {
            minta(sebaran[i]?.kiri);
            minta(sebaran[i]?.kanan);
        }
    }, [indeks, sebaran, minta]);

    const gbr = (nomor: number | null | undefined) => (nomor ? gambar[`${nomor}@${lebarPx}`] : undefined);
    const pertamaDari = (s?: Sebaran) => (s ? (s.kiri ?? s.kanan ?? 1) : 1);

    const pindah = useCallback(
        (arah: Arah) => {
            if (balik) {
                return;
            }
            const tujuan = arah === 'maju' ? sebaran[indeks + 1] : sebaran[indeks - 1];
            if (!tujuan) {
                return;
            }
            if (kurangiGerak) {
                setHalamanAcuan(pertamaDari(tujuan));

                return;
            }
            setBalik(arah);
        },
        [balik, sebaran, indeks, kurangiGerak],
    );

    const selesaiBalik = () => {
        setHalamanAcuan(pertamaDari(balik === 'maju' ? berikut : sebelum));
        setBalik(null);
    };

    const loncat = (nomor: number) => {
        if (!balik) {
            setHalamanAcuan(Math.min(Math.max(1, nomor), total || 1));
        }
    };

    const tombolKeyboard = (e: KeyboardEventReact<HTMLDivElement>) => {
        if (e.key === 'ArrowRight' || e.key === 'PageDown') {
            e.preventDefault();
            pindah('maju');
        } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
            e.preventDefault();
            pindah('mundur');
        } else if (e.key === 'Home') {
            e.preventDefault();
            loncat(1);
        } else if (e.key === 'End') {
            e.preventDefault();
            loncat(total);
        }
    };

    const mulaiGeser = (e: PointerEventReact<HTMLDivElement>) => {
        geser.current = { x: e.clientX, y: e.clientY };
    };
    const akhiriGeser = (e: PointerEventReact<HTMLDivElement>) => {
        const awal = geser.current;
        geser.current = null;
        if (!awal) {
            return;
        }
        const dx = e.clientX - awal.x;
        const dy = e.clientY - awal.y;
        if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
            pindah(dx < 0 ? 'maju' : 'mundur');

            return;
        }
        if (Math.abs(dx) < 6 && Math.abs(dy) < 6) {
            // Klik: sisi kiri = mundur, sisi kanan = maju.
            const kotak = e.currentTarget.getBoundingClientRect();
            pindah(e.clientX - kotak.left < kotak.width / 2 ? 'mundur' : 'maju');
        }
    };

    const alihkanLayarPenuh = () => {
        if (document.fullscreenElement) {
            void document.exitFullscreen();
        } else {
            void layar.current?.requestFullscreen?.();
        }
    };

    const labelHalaman = kini ? [kini.kiri, kini.kanan].filter(Boolean).join('–') : '–';
    const durasi = { duration: 0.8, ease: KUNCI_TRANSISI };

    if (galat) {
        return (
            <p className="flex items-start gap-2 rounded-xl border border-destructive/40 p-4 text-sm text-destructive" role="alert">
                <TriangleAlert className="mt-0.5 size-4 shrink-0" aria-hidden /> Dokumen gagal dimuat dalam mode buku. Gunakan “Mode standar” atau tombol “Buka
                di tab baru”.
            </p>
        );
    }

    const lebarBuku = ganda ? lebarHalaman * 2 : lebarHalaman;

    return (
        <div
            ref={layar}
            className={cn(
                'rounded-2xl bg-gradient-to-b from-zinc-200 to-zinc-300 p-3 sm:p-5 dark:from-zinc-800 dark:to-zinc-900',
                layarPenuh && 'flex flex-col justify-center rounded-none',
            )}
        >
            <div ref={wadah} className="w-full">
                {!doc ? (
                    <div
                        className="flex items-center justify-center gap-2 text-sm text-zinc-600 dark:text-zinc-300"
                        style={{ height: Math.min(tinggiMaks, 480) }}
                        role="status"
                    >
                        <Loader2 className="size-5 animate-spin" aria-hidden /> Menyiapkan buku…
                    </div>
                ) : (
                    <div
                        role="region"
                        aria-roledescription="buku"
                        aria-label={`Penampil buku: ${judul}. Gunakan panah kiri/kanan untuk membalik halaman.`}
                        tabIndex={0}
                        onKeyDown={tombolKeyboard}
                        onPointerDown={mulaiGeser}
                        onPointerUp={akhiriGeser}
                        className="relative mx-auto cursor-pointer touch-pan-y rounded-md outline-none focus-visible:ring-4 focus-visible:ring-primary/50"
                        style={{ width: lebarBuku, height: tinggiHalaman, perspective: ganda ? lebarBuku * 2 : lebarBuku * 3 }}
                    >
                        {/* Bayangan buku di meja */}
                        <div className="pointer-events-none absolute -inset-x-2 -bottom-3 h-6 rounded-[50%] bg-black/25 blur-md" aria-hidden />

                        {ganda ? (
                            <>
                                {/* Halaman kiri statis */}
                                <div className="absolute top-0 left-0 shadow-lg" style={{ width: lebarHalaman, height: tinggiHalaman }}>
                                    {(() => {
                                        const n = balik === 'mundur' ? (sebelum?.kiri ?? null) : (kini?.kiri ?? null);

                                        return <Kertas nomor={n} gambar={gbr(n)} sisi="kiri" />;
                                    })()}
                                </div>
                                {/* Halaman kanan statis */}
                                <div className="absolute top-0 shadow-lg" style={{ left: lebarHalaman, width: lebarHalaman, height: tinggiHalaman }}>
                                    {(() => {
                                        const n = balik === 'maju' ? (berikut?.kanan ?? null) : (kini?.kanan ?? null);

                                        return <Kertas nomor={n} gambar={gbr(n)} sisi="kanan" />;
                                    })()}
                                </div>
                                {/* Lembar yang dibalik */}
                                {balik === 'maju' && (
                                    <motion.div
                                        className="absolute top-0"
                                        style={{
                                            left: lebarHalaman,
                                            width: lebarHalaman,
                                            height: tinggiHalaman,
                                            transformOrigin: 'left center',
                                            transformStyle: 'preserve-3d',
                                        }}
                                        initial={{ rotateY: 0 }}
                                        animate={{ rotateY: -180 }}
                                        transition={durasi}
                                        onAnimationComplete={selesaiBalik}
                                    >
                                        <Kertas nomor={kini?.kanan ?? null} gambar={gbr(kini?.kanan)} sisi="kanan" style={{ backfaceVisibility: 'hidden' }} />
                                        <Kertas
                                            nomor={berikut?.kiri ?? null}
                                            gambar={gbr(berikut?.kiri)}
                                            sisi="kiri"
                                            style={{ backfaceVisibility: 'hidden', transform: 'rotateY(180deg)' }}
                                        />
                                        <motion.div
                                            className="pointer-events-none absolute inset-0 bg-black"
                                            initial={{ opacity: 0 }}
                                            animate={{ opacity: [0, 0.18, 0] }}
                                            transition={durasi}
                                            aria-hidden
                                        />
                                    </motion.div>
                                )}
                                {balik === 'mundur' && (
                                    <motion.div
                                        className="absolute top-0 left-0"
                                        style={{ width: lebarHalaman, height: tinggiHalaman, transformOrigin: 'right center', transformStyle: 'preserve-3d' }}
                                        initial={{ rotateY: 0 }}
                                        animate={{ rotateY: 180 }}
                                        transition={durasi}
                                        onAnimationComplete={selesaiBalik}
                                    >
                                        <Kertas nomor={kini?.kiri ?? null} gambar={gbr(kini?.kiri)} sisi="kiri" style={{ backfaceVisibility: 'hidden' }} />
                                        <Kertas
                                            nomor={sebelum?.kanan ?? null}
                                            gambar={gbr(sebelum?.kanan)}
                                            sisi="kanan"
                                            style={{ backfaceVisibility: 'hidden', transform: 'rotateY(180deg)' }}
                                        />
                                        <motion.div
                                            className="pointer-events-none absolute inset-0 bg-black"
                                            initial={{ opacity: 0 }}
                                            animate={{ opacity: [0, 0.18, 0] }}
                                            transition={durasi}
                                            aria-hidden
                                        />
                                    </motion.div>
                                )}
                                {/* Punggung buku */}
                                <div className="pointer-events-none absolute top-0 h-full w-px bg-black/25" style={{ left: lebarHalaman }} aria-hidden />
                            </>
                        ) : (
                            <>
                                <div className="absolute inset-0 shadow-lg">
                                    {(() => {
                                        const n = balik === 'maju' ? (berikut?.kanan ?? null) : (kini?.kanan ?? null);

                                        return <Kertas nomor={n} gambar={gbr(n)} sisi="tunggal" />;
                                    })()}
                                </div>
                                {balik && (
                                    <motion.div
                                        className="absolute inset-0"
                                        style={{ transformOrigin: 'left center', transformStyle: 'preserve-3d' }}
                                        initial={{ rotateY: balik === 'maju' ? 0 : -180 }}
                                        animate={{ rotateY: balik === 'maju' ? -180 : 0 }}
                                        transition={{ duration: 0.65, ease: KUNCI_TRANSISI }}
                                        onAnimationComplete={selesaiBalik}
                                    >
                                        {(() => {
                                            const n = balik === 'maju' ? (kini?.kanan ?? null) : (sebelum?.kanan ?? null);

                                            return (
                                                <Kertas
                                                    nomor={n}
                                                    gambar={gbr(n)}
                                                    sisi="tunggal"
                                                    style={{ backfaceVisibility: 'hidden' }}
                                                    className="shadow-2xl"
                                                />
                                            );
                                        })()}
                                    </motion.div>
                                )}
                            </>
                        )}
                    </div>
                )}
            </div>

            {doc && (
                <div className="mx-auto mt-5 flex max-w-3xl flex-wrap items-center justify-center gap-2" role="toolbar" aria-label="Kontrol buku">
                    <Button variant="secondary" size="icon" onClick={() => loncat(1)} disabled={indeks === 0 || !!balik} aria-label="Ke halaman pertama">
                        <ChevronFirst />
                    </Button>
                    <Button
                        variant="secondary"
                        size="icon"
                        onClick={() => pindah('mundur')}
                        disabled={indeks === 0 || !!balik}
                        aria-label="Balik ke halaman sebelumnya"
                    >
                        <ChevronLeft />
                    </Button>
                    <label className="flex min-w-48 flex-1 items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                        <span className="sr-only">Pilih halaman</span>
                        <input
                            type="range"
                            min={1}
                            max={total}
                            value={halamanAcuan}
                            onChange={(e) => loncat(Number(e.target.value))}
                            className="w-full accent-merek"
                        />
                    </label>
                    <span className="min-w-28 text-center text-sm font-semibold text-zinc-800 tabular-nums dark:text-zinc-100" aria-live="polite">
                        <BookOpen className="mr-1 inline size-4" aria-hidden />
                        {labelHalaman} / {total}
                    </span>
                    <Button
                        variant="secondary"
                        size="icon"
                        onClick={() => pindah('maju')}
                        disabled={indeks >= sebaran.length - 1 || !!balik}
                        aria-label="Balik ke halaman berikutnya"
                    >
                        <ChevronRight />
                    </Button>
                    <Button
                        variant="secondary"
                        size="icon"
                        onClick={() => loncat(total)}
                        disabled={indeks >= sebaran.length - 1 || !!balik}
                        aria-label="Ke halaman terakhir"
                    >
                        <ChevronLast />
                    </Button>
                    <Button variant="secondary" size="icon" onClick={alihkanLayarPenuh} aria-label={layarPenuh ? 'Keluar layar penuh' : 'Layar penuh'}>
                        {layarPenuh ? <Minimize /> : <Maximize />}
                    </Button>
                </div>
            )}
            {doc && (
                <p className="mt-2 text-center text-xs text-zinc-600 dark:text-zinc-400">
                    Klik sisi kanan/kiri halaman, geser, atau tekan tombol panah untuk membalik halaman.
                </p>
            )}
        </div>
    );
}
