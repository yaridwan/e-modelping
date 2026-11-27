import { Link } from '@inertiajs/react';
import { Download, FileText } from 'lucide-react';
import { DaftarSubbabKartu, KartuBab } from '@/components/publik/kartu';
import { KepalaHalaman } from '@/components/publik/kepala-halaman';
import { Button } from '@/components/ui/button';
import { formatUkuran } from '@/lib/utils';
import type { BabRingkas, Dokumen } from '@/types';

export default function DaftarModul({ bab, modul_lengkap }: { bab: BabRingkas[]; modul_lengkap: Dokumen | null }) {
    return (
        <>
            <KepalaHalaman
                jejak={[{ nama: 'Beranda', url: '/' }, { nama: 'Modul' }]}
                kecil="E-Model Pembiasaan PING"
                judul="Sembilan bab e-modul"
                deskripsi="Telusuri materi dari pendahuluan hingga evaluasi implementasi. Setiap bab dapat dibaca langsung sebagai teks, dengan dokumen PDF pendamping bila tersedia."
            >
                {modul_lengkap && (
                    <div className="mt-6 flex flex-wrap gap-2">
                        <Button asChild className="rounded-full">
                            <Link href={modul_lengkap.url_baca}>
                                <FileText aria-hidden /> Baca modul lengkap (PDF)
                            </Link>
                        </Button>
                        {modul_lengkap.url_unduh && (
                            <Button asChild variant="outline" className="rounded-full">
                                <a href={modul_lengkap.url_unduh} download>
                                    <Download aria-hidden /> Unduh ({formatUkuran(modul_lengkap.ukuran_bita)})
                                </a>
                            </Button>
                        )}
                    </div>
                )}
            </KepalaHalaman>
            <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                {bab.length ? (
                    <ol className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {bab.map((b, i) => (
                            <li key={b.id}>
                                <KartuBab bab={b} jeda={(i % 3) * 0.05} kaki={b.subbab?.length ? <DaftarSubbabKartu bab={b} /> : undefined} />
                            </li>
                        ))}
                    </ol>
                ) : (
                    <p className="text-muted-foreground">Belum ada bab yang diterbitkan.</p>
                )}
            </div>
        </>
    );
}
