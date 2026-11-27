import { Link, useForm } from '@inertiajs/react';
import { ExternalLink, Save } from 'lucide-react';
import { useState } from 'react';
import { BidangPilih, BidangSakelar, BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { EditorTeks } from '@/components/admin/editor-teks';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { TombolKonfirmasi } from '@/components/admin/konfirmasi';
import { BidangMedia } from '@/components/admin/pemilih-media';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type M = { id: number; url: string | null; judul: string } | null;
type H = {
    id: number;
    kode_halaman: string;
    judul: string;
    slug: string;
    ringkasan: string | null;
    isi: string | null;
    judul_seo: string | null;
    deskripsi_seo: string | null;
    gambar_sampul_id: number | null;
    gambar_og_id: number | null;
    tampilkan_di_navigasi: boolean;
    halaman_inti: boolean;
    status: string;
    url: string;
    gambar_sampul: M;
    gambar_og: M;
};

export default function FormHalaman({ halaman }: { halaman: H | null }) {
    const baru = !halaman;
    const [sampul, setSampul] = useState<M>(halaman?.gambar_sampul ?? null);
    const [og, setOg] = useState<M>(halaman?.gambar_og ?? null);
    const form = useForm({
        judul: halaman?.judul ?? '',
        slug: halaman?.slug ?? '',
        ringkasan: halaman?.ringkasan ?? '',
        isi: halaman?.isi ?? '',
        gambar_sampul_id: halaman?.gambar_sampul_id ?? null,
        gambar_og_id: halaman?.gambar_og_id ?? null,
        judul_seo: halaman?.judul_seo ?? '',
        deskripsi_seo: halaman?.deskripsi_seo ?? '',
        tampilkan_di_navigasi: halaman?.tampilkan_di_navigasi ?? false,
        status: halaman?.status ?? 'draf',
    });

    return (
        <HalamanAdmin
            judul={baru ? 'Halaman baru' : halaman.judul}
            jejak={[{ judul: 'Halaman Informasi', href: '/admin/halaman' }, { judul: baru ? 'Baru' : halaman.judul }]}
            aksi={
                !baru && (
                    <>
                        <Button asChild variant="outline" size="sm">
                            <a href={halaman.url} target="_blank" rel="noopener">
                                <ExternalLink /> Lihat
                            </a>
                        </Button>
                        {!halaman.halaman_inti && (
                            <TombolKonfirmasi
                                url={`/admin/halaman/${halaman.id}`}
                                judul="Hapus halaman?"
                                deskripsi="Halaman dan tautan menu yang mengarah ke halaman ini akan dihapus."
                            >
                                Hapus
                            </TombolKonfirmasi>
                        )}
                    </>
                )
            }
        >
            <form
                className="space-y-6"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.transform((d) => (halaman?.halaman_inti ? { ...d, slug: undefined } : d));
                    if (baru) {
                        form.post('/admin/halaman');
                    } else {
                        form.put(`/admin/halaman/${halaman.id}`, { preserveScroll: true });
                    }
                }}
            >
                <KartuAdmin>
                    <div className="grid gap-4 md:grid-cols-2">
                        <BidangTeks id="judul" label="Judul" wajib nilai={form.data.judul} onUbah={(v) => form.setData('judul', v)} galat={form.errors.judul} />
                        {halaman?.halaman_inti ? (
                            <BidangTeks
                                id="slug"
                                label="URL tetap (halaman inti)"
                                nilai={halaman.url}
                                onUbah={() => undefined}
                                petunjuk="Slug halaman inti tidak dapat diubah."
                            />
                        ) : (
                            <BidangTeks
                                id="slug"
                                label="Slug"
                                wajib
                                nilai={form.data.slug}
                                onUbah={(v) => form.setData('slug', v)}
                                galat={form.errors.slug}
                                petunjuk={`URL: /${form.data.slug}`}
                            />
                        )}
                        <BidangPilih
                            id="status"
                            label="Status"
                            nilai={form.data.status}
                            onUbah={(v) => form.setData('status', v)}
                            opsi={[
                                { nilai: 'draf', label: 'Draf' },
                                { nilai: 'terbit', label: 'Terbit' },
                                { nilai: 'arsip', label: 'Arsip' },
                            ]}
                            galat={form.errors.status}
                        />
                        <BidangSakelar
                            id="nav"
                            label="Tandai sebagai halaman navigasi"
                            nilai={form.data.tampilkan_di_navigasi}
                            onUbah={(v) => form.setData('tampilkan_di_navigasi', v)}
                            petunjuk="Atur tautan menu di Menu & Tautan Sosial."
                        />
                        <BidangTextarea
                            id="ringkasan"
                            label="Ringkasan"
                            className="md:col-span-2"
                            baris={2}
                            nilai={form.data.ringkasan}
                            onUbah={(v) => form.setData('ringkasan', v)}
                            galat={form.errors.ringkasan}
                        />
                    </div>
                    <div className="mt-4 grid gap-1.5">
                        <Label htmlFor="isi-halaman">Isi</Label>
                        <EditorTeks id="isi-halaman" label="Isi halaman" nilai={form.data.isi} onUbah={(v) => form.setData('isi', v)} galat={form.errors.isi} />
                    </div>
                </KartuAdmin>
                <KartuAdmin judul="Media & SEO">
                    <div className="grid gap-4 md:grid-cols-2">
                        <BidangMedia
                            id="sampul"
                            label="Gambar sampul"
                            media={sampul}
                            onUbah={(m) => {
                                setSampul(m);
                                form.setData('gambar_sampul_id', m?.id ?? null);
                            }}
                            galat={form.errors.gambar_sampul_id}
                        />
                        <BidangMedia
                            id="og"
                            label="Gambar Open Graph"
                            media={og}
                            onUbah={(m) => {
                                setOg(m);
                                form.setData('gambar_og_id', m?.id ?? null);
                            }}
                            galat={form.errors.gambar_og_id}
                        />
                        <BidangTeks
                            id="judul_seo"
                            label="Judul SEO"
                            nilai={form.data.judul_seo}
                            onUbah={(v) => form.setData('judul_seo', v)}
                            galat={form.errors.judul_seo}
                        />
                        <BidangTextarea
                            id="deskripsi_seo"
                            label="Deskripsi SEO"
                            baris={2}
                            nilai={form.data.deskripsi_seo}
                            onUbah={(v) => form.setData('deskripsi_seo', v)}
                            galat={form.errors.deskripsi_seo}
                            maks={300}
                        />
                    </div>
                </KartuAdmin>
                <div className="flex gap-2">
                    <Button type="submit" disabled={form.processing}>
                        <Save /> Simpan halaman
                    </Button>
                    <Button asChild variant="outline">
                        <Link href="/admin/halaman">Kembali</Link>
                    </Button>
                </div>
            </form>
        </HalamanAdmin>
    );
}
