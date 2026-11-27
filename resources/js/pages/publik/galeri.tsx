import { Link } from '@inertiajs/react';
import { Halaman } from '@/components/admin/daftar';
import { KepalaHalaman } from '@/components/publik/kepala-halaman';
import { HtmlAman } from '@/components/umum/html-aman';
import { formatTanggal } from '@/lib/utils';
import type { Media, Paginasi } from '@/types';

type Item = {
    id: number;
    judul: string;
    ringkasan: string | null;
    keterangan: string | null;
    tanggal: string | null;
    lokasi: string | null;
    alt: string;
    media: Media | null;
    fase: { nama: string; url: string } | null;
    bab: { judul: string; url: string } | null;
};

export default function Galeri({
    pengantar,
    galeri,
}: {
    pengantar: { judul: string; ringkasan: string | null; isi: string | null } | null;
    galeri: Paginasi<Item>;
}) {
    return (
        <>
            <KepalaHalaman jejak={[{ nama: 'Beranda', url: '/' }, { nama: 'Galeri' }]} judul={pengantar?.judul ?? 'Galeri'} deskripsi={pengantar?.ringkasan} />
            <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                <HtmlAman html={pengantar?.isi} className="mb-8 max-w-3xl text-muted-foreground" />
                {galeri.data.length ? (
                    <>
                        <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {galeri.data.map((g) => (
                                <li key={g.id} className="overflow-hidden rounded-2xl border bg-card">
                                    {g.media?.url && (
                                        <img
                                            src={g.media.url}
                                            alt={g.alt}
                                            width={g.media.lebar ?? 800}
                                            height={g.media.tinggi ?? 600}
                                            loading="lazy"
                                            className="aspect-[4/3] w-full object-cover"
                                        />
                                    )}
                                    <div className="space-y-1 p-4">
                                        <h2 className="font-bold">{g.judul}</h2>
                                        {g.ringkasan && <p className="text-sm text-foreground/80">{g.ringkasan}</p>}
                                        <p className="text-xs text-muted-foreground">
                                            {[g.tanggal && formatTanggal(g.tanggal), g.lokasi].filter(Boolean).join(' · ')}
                                        </p>
                                        <p className="flex flex-wrap gap-3 pt-1 text-xs">
                                            {g.fase && (
                                                <Link href={g.fase.url} className="text-primary underline">
                                                    {g.fase.nama}
                                                </Link>
                                            )}
                                            {g.bab && (
                                                <Link href={g.bab.url} className="text-primary underline">
                                                    {g.bab.judul}
                                                </Link>
                                            )}
                                        </p>
                                        {g.media?.sumber && <p className="text-xs text-muted-foreground">Sumber: {g.media.sumber}</p>}
                                    </div>
                                </li>
                            ))}
                        </ul>
                        <Halaman data={galeri} />
                    </>
                ) : (
                    <p className="rounded-2xl border border-dashed p-10 text-center text-muted-foreground">Belum ada media galeri yang diterbitkan.</p>
                )}
            </div>
        </>
    );
}
