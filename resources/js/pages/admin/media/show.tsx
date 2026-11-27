import { Link, useForm } from '@inertiajs/react';
import { ExternalLink, Save } from 'lucide-react';
import { BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { TombolKonfirmasi } from '@/components/admin/konfirmasi';
import { Button } from '@/components/ui/button';
import { formatTanggal, formatUkuran } from '@/lib/utils';
import type { MediaAdmin } from '@/types';

type M = MediaAdmin & {
    keterangan: string | null;
    sumber: string | null;
    lisensi: string | null;
    nama_asli: string | null;
    url_eksternal: string | null;
    url_embed: string | null;
    url_publik: string | null;
    checksum: string | null;
};

export default function DetailMedia({ media, penggunaan }: { media: M; penggunaan: { jenis: string; label: string; tautan: string | null }[] }) {
    const form = useForm({
        judul: media.judul,
        teks_alternatif: media.teks_alternatif ?? '',
        keterangan: media.keterangan ?? '',
        sumber: media.sumber ?? '',
        lisensi: media.lisensi ?? '',
    });

    return (
        <HalamanAdmin
            judul={media.judul}
            jejak={[{ judul: 'Pustaka Media', href: '/admin/media' }, { judul: media.judul }]}
            aksi={
                penggunaan.length === 0 ? (
                    <TombolKonfirmasi
                        url={`/admin/media/${media.id}`}
                        judul="Hapus media?"
                        deskripsi="Berkas akan dihapus permanen dari penyimpanan dan tidak dapat dipulihkan."
                    >
                        Hapus media
                    </TombolKonfirmasi>
                ) : (
                    <Button variant="outline" disabled title="Media masih digunakan">
                        Tidak dapat dihapus (dipakai {penggunaan.length}×)
                    </Button>
                )
            }
        >
            <div className="grid gap-6 lg:grid-cols-[1fr_22rem]">
                <KartuAdmin>
                    {media.jenis === 'gambar' && media.url && (
                        <img src={media.url} alt={media.teks_alternatif ?? ''} className="max-h-[60vh] rounded-lg border object-contain" />
                    )}
                    {media.jenis === 'pdf' && media.url && (
                        <Button asChild variant="outline">
                            <a href={media.url} target="_blank" rel="noopener">
                                <ExternalLink /> Buka PDF (privat admin)
                            </a>
                        </Button>
                    )}
                    {media.jenis === 'video_eksternal' && media.url_embed && (
                        <div className="aspect-video">
                            <iframe
                                src={media.url_embed}
                                title={media.judul}
                                className="size-full rounded-lg"
                                allowFullScreen
                                sandbox="allow-scripts allow-same-origin allow-presentation"
                            />
                        </div>
                    )}
                    <form
                        className="mt-6 grid gap-4 md:grid-cols-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.put(`/admin/media/${media.id}`, { preserveScroll: true });
                        }}
                    >
                        <BidangTeks id="judul" label="Judul" wajib nilai={form.data.judul} onUbah={(v) => form.setData('judul', v)} galat={form.errors.judul} />
                        <BidangTeks
                            id="alt"
                            label="Teks alternatif"
                            wajib={media.jenis === 'gambar'}
                            nilai={form.data.teks_alternatif}
                            onUbah={(v) => form.setData('teks_alternatif', v)}
                            galat={form.errors.teks_alternatif}
                        />
                        <BidangTeks id="sumber" label="Sumber" nilai={form.data.sumber} onUbah={(v) => form.setData('sumber', v)} />
                        <BidangTeks id="lisensi" label="Lisensi" nilai={form.data.lisensi} onUbah={(v) => form.setData('lisensi', v)} />
                        <BidangTextarea
                            id="keterangan"
                            label="Keterangan"
                            className="md:col-span-2"
                            nilai={form.data.keterangan}
                            onUbah={(v) => form.setData('keterangan', v)}
                        />
                        <Button type="submit" className="w-fit" disabled={form.processing}>
                            <Save /> Simpan metadata
                        </Button>
                    </form>
                </KartuAdmin>
                <div className="space-y-6">
                    <KartuAdmin judul="Detail berkas">
                        <dl className="space-y-1.5 text-sm">
                            {[
                                ['Jenis', media.jenis],
                                ['Nama asli', media.nama_asli],
                                ['MIME', media.tipe_mime],
                                ['Ukuran', formatUkuran(media.ukuran_bita)],
                                ['Dimensi', media.lebar ? `${media.lebar}×${media.tinggi}px` : null],
                                ['Halaman', media.jumlah_halaman],
                                ['SHA-256', media.checksum],
                                ['Diunggah', formatTanggal(media.dibuat_pada, true)],
                                ['URL publik', media.url_publik ?? 'Tidak publik (belum dirujuk konten terbit)'],
                            ].map(([k, v]) => (
                                <div key={String(k)} className="flex justify-between gap-2 border-b pb-1">
                                    <dt className="text-muted-foreground">{k}</dt>
                                    <dd className="text-right break-all">{v ?? '—'}</dd>
                                </div>
                            ))}
                        </dl>
                    </KartuAdmin>
                    <KartuAdmin judul="Penggunaan" deskripsi="Media yang masih digunakan tidak dapat dihapus.">
                        {penggunaan.length ? (
                            <ul className="space-y-1.5 text-sm">
                                {penggunaan.map((p, i) => (
                                    <li key={i}>
                                        {p.tautan ? (
                                            <Link href={p.tautan} className="text-primary hover:underline">
                                                {p.label}
                                            </Link>
                                        ) : (
                                            p.label
                                        )}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-muted-foreground">Tidak digunakan di mana pun.</p>
                        )}
                    </KartuAdmin>
                </div>
            </div>
        </HalamanAdmin>
    );
}
