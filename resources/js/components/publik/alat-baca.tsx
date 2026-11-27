import { Bookmark, BookmarkCheck, Check, Link2, ListTree, Minus, Plus, Share2, Trash2, Type } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Popover } from '@/components/publik/popover-sederhana';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { cn, penyimpanan } from '@/lib/utils';

export type PreferensiBaca = { ukuran: number; lebar: 'sempit' | 'sedang' | 'lebar' };
const BAWAAN: PreferensiBaca = { ukuran: 17, lebar: 'sedang' };
const KUNCI_PREF = 'ping.preferensi_baca';
const KUNCI_PENANDA = 'ping.penanda_baca';

export const LEBAR_BACA: Record<PreferensiBaca['lebar'], string> = { sempit: '36rem', sedang: '44rem', lebar: '54rem' };

/** Preferensi baca disimpan di perangkat (localStorage), tanpa akun dan tanpa server. */
export function usePreferensiBaca() {
    const [pref, setPref] = useState<PreferensiBaca>(BAWAAN);
    useEffect(() => setPref(penyimpanan.ambil(KUNCI_PREF, BAWAAN)), []);
    const ubah = useCallback((p: Partial<PreferensiBaca>) => {
        setPref((lama) => {
            const baru = { ...lama, ...p };
            penyimpanan.simpan(KUNCI_PREF, baru);

            return baru;
        });
    }, []);

    return { pref, ubah };
}

type Penanda = Record<string, { judul: string; url: string; posisi?: string; disimpan: string }>;

export function usePenandaBaca(slug: string, judul: string) {
    const [penanda, setPenanda] = useState<Penanda>({});
    useEffect(() => setPenanda(penyimpanan.ambil<Penanda>(KUNCI_PENANDA, {})), []);
    const simpan = (p: Penanda) => {
        setPenanda(p);
        penyimpanan.simpan(KUNCI_PENANDA, p);
    };

    return {
        ditandai: !!penanda[slug],
        semua: penanda,
        posisiTerakhir: penanda[slug]?.posisi,
        alihkan: () => {
            const p = { ...penanda };
            if (p[slug]) {
                delete p[slug];
            } else {
                p[slug] = { judul, url: `/modul/${slug}`, disimpan: new Date().toISOString() };
            }
            simpan(p);
        },
        catatPosisi: (posisi: string) => {
            const p = penyimpanan.ambil<Penanda>(KUNCI_PENANDA, {});
            if (p[slug]) {
                p[slug] = { ...p[slug], posisi };
                penyimpanan.simpan(KUNCI_PENANDA, p);
            }
        },
        hapusSemua: () => {
            penyimpanan.hapus(KUNCI_PENANDA);
            penyimpanan.hapus(KUNCI_PREF);
            setPenanda({});
        },
    };
}

export function PengaturanBaca({ pref, ubah }: { pref: PreferensiBaca; ubah: (p: Partial<PreferensiBaca>) => void }) {
    return (
        <Popover
            label="Pengaturan tampilan baca"
            pemicu={
                <>
                    <Type className="size-4" aria-hidden /> Tampilan baca
                </>
            }
        >
            <div className="w-64 space-y-4">
                <div>
                    <p className="mb-2 text-sm font-semibold">Ukuran huruf</p>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="icon" onClick={() => ubah({ ukuran: Math.max(14, pref.ukuran - 1) })} aria-label="Perkecil huruf">
                            <Minus />
                        </Button>
                        <span className="flex-1 text-center text-sm tabular-nums" aria-live="polite">
                            {pref.ukuran}px
                        </span>
                        <Button variant="outline" size="icon" onClick={() => ubah({ ukuran: Math.min(24, pref.ukuran + 1) })} aria-label="Perbesar huruf">
                            <Plus />
                        </Button>
                    </div>
                </div>
                <fieldset>
                    <legend className="mb-2 text-sm font-semibold">Lebar baca</legend>
                    <div className="grid grid-cols-3 gap-1">
                        {(['sempit', 'sedang', 'lebar'] as const).map((l) => (
                            <button
                                key={l}
                                type="button"
                                onClick={() => ubah({ lebar: l })}
                                aria-pressed={pref.lebar === l}
                                className={cn(
                                    'rounded-md border px-2 py-1.5 text-sm capitalize',
                                    pref.lebar === l ? 'border-primary bg-secondary font-semibold text-secondary-foreground' : 'hover:bg-accent',
                                )}
                            >
                                {l}
                            </button>
                        ))}
                    </div>
                </fieldset>
                <p className="text-xs text-muted-foreground">Tersimpan di perangkat ini saja.</p>
            </div>
        </Popover>
    );
}

export function TombolPenanda({ ditandai, alihkan }: { ditandai: boolean; alihkan: () => void }) {
    return (
        <Button variant="outline" size="sm" onClick={alihkan} aria-pressed={ditandai}>
            {ditandai ? <BookmarkCheck className="text-primary" /> : <Bookmark />}
            {ditandai ? 'Ditandai' : 'Tandai bab'}
        </Button>
    );
}

export function TombolBagikan({ judul }: { judul: string }) {
    const [disalin, setDisalin] = useState(false);
    const salin = async () => {
        try {
            await navigator.clipboard.writeText(window.location.href.split('#')[0]);
            setDisalin(true);
            setTimeout(() => setDisalin(false), 2000);
        } catch {
            window.prompt('Salin tautan berikut:', window.location.href);
        }
    };
    const bagikan = async () => {
        if (typeof navigator !== 'undefined' && 'share' in navigator) {
            try {
                await navigator.share({ title: judul, url: window.location.href.split('#')[0] });
            } catch {
                /* dibatalkan pengguna */
            }
        } else {
            await salin();
        }
    };

    return (
        <>
            <Button variant="outline" size="sm" onClick={salin}>
                {disalin ? <Check className="text-emerald-600" /> : <Link2 />}
                <span aria-live="polite">{disalin ? 'Tersalin' : 'Salin tautan'}</span>
            </Button>
            <Button variant="outline" size="sm" onClick={bagikan}>
                <Share2 /> Bagikan
            </Button>
        </>
    );
}

export function TombolHapusRiwayat({ onHapus }: { onHapus: () => void }) {
    return (
        <button
            type="button"
            onClick={onHapus}
            className="inline-flex items-center gap-1 text-xs text-muted-foreground underline-offset-2 hover:text-destructive hover:underline"
        >
            <Trash2 className="size-3.5" aria-hidden /> Hapus riwayat baca di perangkat ini
        </button>
    );
}

export type ItemDaftarIsi = { id: string; label: string; kode?: string };

function useSubbabAktif(ids: string[]) {
    const [aktif, setAktif] = useState<string | null>(null);
    useEffect(() => {
        if (typeof IntersectionObserver === 'undefined') {
            return;
        }
        const pengamat = new IntersectionObserver(
            (entri) => {
                const terlihat = entri.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
                if (terlihat[0]) {
                    setAktif(terlihat[0].target.id);
                }
            },
            { rootMargin: '-90px 0px -65% 0px' },
        );
        ids.forEach((id) => {
            const el = document.getElementById(id);
            if (el) {
                pengamat.observe(el);
            }
        });

        return () => pengamat.disconnect();
    }, [ids]);

    return aktif;
}

function DaftarTautan({ item, aktif, onPilih }: { item: ItemDaftarIsi[]; aktif: string | null; onPilih?: () => void }) {
    return (
        <ol className="space-y-0.5 border-l">
            {item.map((i) => (
                <li key={i.id}>
                    <a
                        href={`#${i.id}`}
                        onClick={onPilih}
                        aria-current={aktif === i.id ? 'location' : undefined}
                        className={cn(
                            '-ml-px block border-l-2 py-1.5 pr-2 pl-3 text-sm leading-snug transition-colors',
                            aktif === i.id
                                ? 'border-primary font-semibold text-primary'
                                : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                        )}
                    >
                        {i.kode && <span className="mr-1 font-mono text-xs opacity-70">{i.kode}</span>}
                        {i.label}
                    </a>
                </li>
            ))}
        </ol>
    );
}

/** Daftar isi: lengket di desktop, drawer di ponsel. Tautan biasa (#id) sehingga berfungsi tanpa JS. */
export function DaftarIsi({ item, onAktif }: { item: ItemDaftarIsi[]; onAktif?: (id: string) => void }) {
    const aktif = useSubbabAktif(item.map((i) => i.id));
    const [buka, setBuka] = useState(false);
    useEffect(() => {
        if (aktif) {
            onAktif?.(aktif);
        }
    }, [aktif, onAktif]);

    return (
        <>
            <nav aria-label="Daftar isi bab" className="sticky top-24 hidden max-h-[calc(100vh-8rem)] overflow-y-auto lg:block">
                <p className="mb-3 text-xs font-bold tracking-widest text-muted-foreground uppercase">Daftar isi</p>
                <DaftarTautan item={item} aktif={aktif} />
            </nav>
            <div className="fixed right-4 bottom-4 z-30 lg:hidden">
                <Sheet open={buka} onOpenChange={setBuka}>
                    <SheetTrigger asChild>
                        <Button className="rounded-full shadow-lg" aria-label="Buka daftar isi">
                            <ListTree /> Daftar isi
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="bottom" className="max-h-[75vh] overflow-y-auto">
                        <SheetHeader>
                            <SheetTitle>Daftar isi</SheetTitle>
                        </SheetHeader>
                        <div className="px-4 pb-6">
                            <DaftarTautan item={item} aktif={aktif} onPilih={() => setBuka(false)} />
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </>
    );
}
