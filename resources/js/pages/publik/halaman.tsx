import { KepalaHalaman } from '@/components/publik/kepala-halaman';
import { HtmlAman } from '@/components/umum/html-aman';
import { formatTanggal } from '@/lib/utils';
import type { Media } from '@/types';

export default function HalamanInformasi({
    halaman,
}: {
    halaman: { judul: string; ringkasan: string | null; isi: string | null; sampul: Media | null; diperbarui_pada: string | null };
}) {
    return (
        <>
            <KepalaHalaman jejak={[{ nama: 'Beranda', url: '/' }, { nama: halaman.judul }]} judul={halaman.judul} deskripsi={halaman.ringkasan} />
            <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6">
                {halaman.sampul?.url && (
                    <img
                        src={halaman.sampul.url}
                        alt={halaman.sampul.alt}
                        width={halaman.sampul.lebar ?? 1200}
                        height={halaman.sampul.tinggi ?? 600}
                        className="mb-8 w-full rounded-2xl"
                    />
                )}
                <HtmlAman html={halaman.isi} />
                {halaman.diperbarui_pada && (
                    <p className="mt-10 text-sm text-muted-foreground">Terakhir diperbarui {formatTanggal(halaman.diperbarui_pada)}.</p>
                )}
            </div>
        </>
    );
}
