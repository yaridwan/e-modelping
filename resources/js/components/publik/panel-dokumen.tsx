import { BookOpen, Download, ExternalLink, FileText, FileX2, ScrollText } from 'lucide-react';
import { lazy, useEffect, useState } from 'react';
import { KlienSaja } from '@/components/umum/klien-saja';
import { Button } from '@/components/ui/button';
import { cn, formatTanggal, formatUkuran, penyimpanan } from '@/lib/utils';
import type { Dokumen } from '@/types';

const PenampilPdf = lazy(() => import('@/components/publik/penampil-pdf'));
const PenampilBuku = lazy(() => import('@/components/publik/penampil-buku'));

type ModePenampil = 'buku' | 'standar';
const KUNCI_MODE = 'ping:mode-penampil';

/**
 * Panel dokumen PDF.
 * - `tata = 'bab'` (halaman bab): penampil hanya dimuat setelah pembaca memintanya.
 * - `tata = 'halaman'` (/dokumen/{id}/baca): penampil langsung tampil, default mode buku (flipbook).
 * Pilihan mode buku/standar diingat di perangkat pembaca.
 */
export function PanelDokumen({ dokumen, judulBab, tata = 'bab' }: { dokumen: Dokumen | null; judulBab: string; tata?: 'bab' | 'halaman' }) {
    const halamanPenuh = tata === 'halaman';
    const [tampil, setTampil] = useState(halamanPenuh);
    const [mode, setModeState] = useState<ModePenampil>('buku');

    // Preferensi dibaca setelah hidrasi agar HTML SSR dan klien identik.
    useEffect(() => {
        if (penyimpanan.ambil<string>(KUNCI_MODE, 'buku') === 'standar') {
            setModeState('standar');
        }
    }, []);

    const setMode = (m: ModePenampil) => {
        setModeState(m);
        penyimpanan.simpan(KUNCI_MODE, m);
    };

    if (!dokumen) {
        return (
            <section aria-labelledby="judul-dokumen" className="rounded-2xl border border-dashed bg-card p-6">
                <h2 id="judul-dokumen" className="flex items-center gap-2 text-lg font-bold">
                    <FileX2 className="size-5 text-muted-foreground" aria-hidden /> Dokumen PDF
                </h2>
                <p className="mt-2 text-sm text-muted-foreground">
                    Dokumen PDF untuk bab ini belum tersedia. Materi lengkap tetap dapat dibaca pada halaman ini.
                </p>
                <div className="mt-4 flex flex-wrap gap-2" aria-hidden>
                    <Button variant="outline" size="sm" disabled>
                        <ExternalLink /> Buka di tab baru
                    </Button>
                    <Button variant="outline" size="sm" disabled>
                        <Download /> Unduh
                    </Button>
                </div>
            </section>
        );
    }

    const judulPenampil = judulBab === dokumen.judul ? dokumen.judul : `${judulBab} — ${dokumen.judul}`;
    const menunggu = <p className="py-10 text-center text-sm text-muted-foreground">Menyiapkan penampil…</p>;

    return (
        <section aria-labelledby="judul-dokumen" className="rounded-2xl border bg-card p-4 sm:p-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 id="judul-dokumen" className="flex items-center gap-2 text-lg font-bold">
                        <FileText className="size-5 text-primary" aria-hidden /> {dokumen.judul}
                    </h2>
                    <dl className="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-muted-foreground">
                        <div className="flex gap-1">
                            <dt>Versi</dt>
                            <dd className="font-semibold text-foreground">{dokumen.nomor_versi}</dd>
                        </div>
                        <div className="flex gap-1">
                            <dt>Ukuran</dt>
                            <dd className="font-semibold text-foreground">{formatUkuran(dokumen.ukuran_bita)}</dd>
                        </div>
                        {dokumen.jumlah_halaman && (
                            <div className="flex gap-1">
                                <dt>Halaman</dt>
                                <dd className="font-semibold text-foreground">{dokumen.jumlah_halaman}</dd>
                            </div>
                        )}
                        {dokumen.diterbitkan_pada && (
                            <div className="flex gap-1">
                                <dt>Terbit</dt>
                                <dd className="font-semibold text-foreground">{formatTanggal(dokumen.diterbitkan_pada)}</dd>
                            </div>
                        )}
                    </dl>
                </div>
                <div className="flex flex-wrap gap-2">
                    {halamanPenuh ? null : (
                        <>
                            <Button asChild size="sm">
                                <a href={dokumen.url_baca}>
                                    <BookOpen /> Baca seperti buku
                                </a>
                            </Button>
                            <Button variant="secondary" size="sm" onClick={() => setTampil((t) => !t)} aria-expanded={tampil} aria-controls="penampil-dokumen">
                                <FileText /> {tampil ? 'Tutup penampil' : 'Baca PDF di sini'}
                            </Button>
                        </>
                    )}
                    <Button asChild variant="outline" size="sm">
                        <a href={dokumen.url_berkas} target="_blank" rel="noopener">
                            <ExternalLink /> Buka di tab baru
                        </a>
                    </Button>
                    {dokumen.url_unduh ? (
                        <Button asChild variant="outline" size="sm">
                            <a href={dokumen.url_unduh} download>
                                <Download /> Unduh
                            </a>
                        </Button>
                    ) : (
                        <Button variant="outline" size="sm" disabled title="Unduhan tidak diizinkan pengelola">
                            <Download /> Unduhan tidak tersedia
                        </Button>
                    )}
                </div>
            </div>

            {tampil && (
                <div id="penampil-dokumen" className="mt-5">
                    {halamanPenuh && (
                        <div role="radiogroup" aria-label="Mode tampilan PDF" className="mb-4 inline-flex rounded-full border bg-muted p-1">
                            {(
                                [
                                    { nilai: 'buku', label: 'Mode buku', Ikon: BookOpen },
                                    { nilai: 'standar', label: 'Mode standar', Ikon: ScrollText },
                                ] as const
                            ).map(({ nilai, label, Ikon }) => (
                                <button
                                    key={nilai}
                                    type="button"
                                    role="radio"
                                    aria-checked={mode === nilai}
                                    onClick={() => setMode(nilai)}
                                    className={cn(
                                        'inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-semibold transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                        mode === nilai ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    <Ikon className="size-4" aria-hidden /> {label}
                                </button>
                            ))}
                        </div>
                    )}
                    <KlienSaja fallback={menunggu}>
                        {halamanPenuh && mode === 'buku' ? (
                            <PenampilBuku url={dokumen.url_berkas} judul={judulPenampil} />
                        ) : (
                            <PenampilPdf url={dokumen.url_berkas} judul={judulPenampil} />
                        )}
                    </KlienSaja>
                    {!dokumen.boleh_unduh && (
                        <p className="mt-2 text-xs text-muted-foreground">
                            Catatan: menonaktifkan unduhan adalah kebijakan tampilan, bukan perlindungan salinan (DRM).
                        </p>
                    )}
                </div>
            )}
        </section>
    );
}
