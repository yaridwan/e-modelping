import { Link, useForm } from '@inertiajs/react';
import { FileText, ImageIcon, Upload, Video } from 'lucide-react';
import { Bidang, BidangPilih, BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { Halaman, KotakCari, PilihFilter, terapkanFilter } from '@/components/admin/daftar';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatTanggal, formatUkuran } from '@/lib/utils';
import type { MediaAdmin, Paginasi } from '@/types';

export default function PustakaMedia({
    media,
    filter,
    batas_mb,
    total_ukuran,
}: {
    media: Paginasi<MediaAdmin>;
    filter: { q: string; urut: string; arah: string; per: number; jenis: string | null };
    batas_mb: number;
    total_ukuran: number;
}) {
    const url = '/admin/media';
    const form = useForm<{
        jenis_media: string;
        berkas: File | null;
        url_eksternal: string;
        judul: string;
        teks_alternatif: string;
        keterangan: string;
        sumber: string;
        lisensi: string;
    }>({ jenis_media: 'gambar', berkas: null, url_eksternal: '', judul: '', teks_alternatif: '', keterangan: '', sumber: '', lisensi: '' });

    return (
        <HalamanAdmin
            judul="Pustaka Media"
            jejak={[{ judul: 'Pustaka Media' }]}
            deskripsi={`Gambar (JPG/PNG/WebP, diproses ulang), PDF, dan tautan video YouTube/Vimeo. Total ukuran: ${formatUkuran(total_ukuran)}. SVG unggahan tidak diterima.`}
        >
            <KartuAdmin judul="Tambah media">
                <form
                    className="grid gap-4 md:grid-cols-2"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/admin/media', { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() });
                    }}
                >
                    <BidangPilih
                        id="jenis_media"
                        label="Jenis"
                        nilai={form.data.jenis_media}
                        onUbah={(v) => form.setData('jenis_media', v)}
                        opsi={[
                            { nilai: 'gambar', label: 'Gambar' },
                            { nilai: 'pdf', label: 'PDF' },
                            { nilai: 'video_eksternal', label: 'Video eksternal (YouTube/Vimeo)' },
                        ]}
                    />
                    {form.data.jenis_media === 'video_eksternal' ? (
                        <BidangTeks
                            id="url_eksternal"
                            label="URL video (https)"
                            wajib
                            nilai={form.data.url_eksternal}
                            onUbah={(v) => form.setData('url_eksternal', v)}
                            galat={form.errors.url_eksternal}
                        />
                    ) : (
                        <Bidang id="berkas" label="Berkas" wajib galat={form.errors.berkas} petunjuk={`Maksimal ${batas_mb} MB.`}>
                            <Input
                                id="berkas"
                                type="file"
                                accept={form.data.jenis_media === 'pdf' ? 'application/pdf' : 'image/jpeg,image/png,image/webp'}
                                onChange={(e) => form.setData('berkas', e.target.files?.[0] ?? null)}
                            />
                        </Bidang>
                    )}
                    <BidangTeks id="judul" label="Judul" nilai={form.data.judul} onUbah={(v) => form.setData('judul', v)} galat={form.errors.judul} />
                    {form.data.jenis_media === 'gambar' && (
                        <BidangTeks
                            id="teks_alternatif"
                            label="Teks alternatif"
                            wajib
                            nilai={form.data.teks_alternatif}
                            onUbah={(v) => form.setData('teks_alternatif', v)}
                            galat={form.errors.teks_alternatif}
                        />
                    )}
                    <BidangTeks id="sumber" label="Sumber" nilai={form.data.sumber} onUbah={(v) => form.setData('sumber', v)} galat={form.errors.sumber} />
                    <BidangTeks id="lisensi" label="Lisensi" nilai={form.data.lisensi} onUbah={(v) => form.setData('lisensi', v)} galat={form.errors.lisensi} />
                    <BidangTextarea
                        id="keterangan"
                        label="Keterangan"
                        className="md:col-span-2"
                        baris={2}
                        nilai={form.data.keterangan}
                        onUbah={(v) => form.setData('keterangan', v)}
                    />
                    <Button type="submit" className="w-fit" disabled={form.processing}>
                        <Upload /> {form.processing ? 'Memproses…' : 'Tambahkan'}
                    </Button>
                </form>
            </KartuAdmin>
            <div className="mt-6">
                <KotakCari url={url} filter={filter} placeholder="Cari judul atau nama berkas…">
                    <PilihFilter
                        label="Jenis"
                        nilai={filter.jenis}
                        onUbah={(v) => terapkanFilter(url, { ...filter, jenis: v })}
                        opsi={[
                            { nilai: 'gambar', label: 'Gambar' },
                            { nilai: 'pdf', label: 'PDF' },
                            { nilai: 'video_eksternal', label: 'Video' },
                        ]}
                    />
                </KotakCari>
                {media.data.length ? (
                    <ul className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                        {media.data.map((m) => {
                            const Ikon = m.jenis === 'pdf' ? FileText : m.jenis === 'video_eksternal' ? Video : ImageIcon;

                            return (
                                <li key={m.id}>
                                    <Link href={`/admin/media/${m.id}`} className="block overflow-hidden rounded-xl border bg-card hover:border-primary">
                                        <div className="flex aspect-square items-center justify-center bg-muted">
                                            {m.jenis === 'gambar' && m.url ? (
                                                <img src={m.url} alt="" loading="lazy" className="size-full object-cover" />
                                            ) : (
                                                <Ikon className="size-10 text-muted-foreground" aria-hidden />
                                            )}
                                        </div>
                                        <div className="p-2">
                                            <p className="truncate text-sm font-medium">{m.judul}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {m.jenis === 'video_eksternal' ? 'Video' : formatUkuran(m.ukuran_bita)} · {formatTanggal(m.dibuat_pada)}
                                            </p>
                                        </div>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                ) : (
                    <p className="mt-6 text-sm text-muted-foreground">Belum ada media yang cocok.</p>
                )}
                <Halaman data={media} />
            </div>
        </HalamanAdmin>
    );
}
