import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { KepalaHalaman } from '@/components/publik/kepala-halaman';
import { PanelDokumen } from '@/components/publik/panel-dokumen';
import type { Dokumen } from '@/types';

export default function BacaDokumen({ dokumen, kembali }: { dokumen: Dokumen; kembali: { label: string; url: string } }) {
    return (
        <>
            <KepalaHalaman
                jejak={[{ nama: 'Beranda', url: '/' }, { nama: 'Modul', url: '/modul' }, { nama: dokumen.judul }]}
                judul={dokumen.judul}
                deskripsi={`Versi ${dokumen.nomor_versi}. Baca dalam mode buku dengan membalik halaman, atau pilih mode standar. Materi utama juga tersedia sebagai teks pada halaman bab.`}
            />
            <div className="mx-auto max-w-6xl space-y-6 px-4 py-10 sm:px-6">
                <Link href={kembali.url} className="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline">
                    <ArrowLeft className="size-4" aria-hidden /> Kembali ke {kembali.label}
                </Link>
                <PanelDokumen dokumen={dokumen} judulBab={dokumen.judul} tata="halaman" />
            </div>
        </>
    );
}
