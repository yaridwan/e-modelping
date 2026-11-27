import { Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useState } from 'react';
import { BidangPilih, BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { EditorTeks } from '@/components/admin/editor-teks';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { TombolKonfirmasi } from '@/components/admin/konfirmasi';
import { BidangMedia } from '@/components/admin/pemilih-media';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type Props = {
    bab: { id: number; nomor_romawi: string; judul: string };
    subbab: null | {
        id: number;
        kode_subbab: string;
        judul: string;
        slug: string;
        ringkasan: string | null;
        isi: string;
        contoh_praktik: string | null;
        media_id: number | null;
        status: string;
        media: { id: number; url: string | null; judul: string } | null;
        metadata_sumber: Record<string, unknown> | null;
    };
    kode_saran: string;
};

const keSlug = (t: string) =>
    t
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

export default function FormSubbab({ bab, subbab, kode_saran }: Props) {
    const baru = !subbab;
    const [media, setMedia] = useState(subbab?.media ?? null);
    const form = useForm({
        kode_subbab: subbab?.kode_subbab ?? kode_saran,
        judul: subbab?.judul ?? '',
        slug: subbab?.slug ?? '',
        ringkasan: subbab?.ringkasan ?? '',
        isi: subbab?.isi ?? '',
        contoh_praktik: subbab?.contoh_praktik ?? '',
        media_id: subbab?.media_id ?? null,
        status: subbab?.status ?? 'draf',
    });
    const kirim = (e: React.FormEvent) => {
        e.preventDefault();
        if (baru) {
            form.post(`/admin/bab/${bab.id}/subbab`);
        } else {
            form.put(`/admin/bab/${bab.id}/subbab/${subbab.id}`, { preserveScroll: true });
        }
    };

    return (
        <HalamanAdmin
            judul={baru ? 'Tambah subbab' : `Sunting subbab: ${subbab.judul}`}
            jejak={[
                { judul: 'Bab & Subbab', href: '/admin/bab' },
                { judul: `Bab ${bab.nomor_romawi}`, href: `/admin/bab/${bab.id}/edit` },
                { judul: baru ? 'Subbab baru' : subbab.kode_subbab },
            ]}
            deskripsi="Subbab berstatus draf/arsip tetap tersembunyi dari publik meskipun bab induk terbit."
            aksi={
                !baru && (
                    <TombolKonfirmasi
                        url={`/admin/bab/${bab.id}/subbab/${subbab.id}`}
                        judul="Hapus subbab?"
                        deskripsi="Subbab akan dihapus permanen. Bab harus menyisakan minimal satu subbab. Pertimbangkan status arsip sebagai alternatif."
                    >
                        Hapus subbab
                    </TombolKonfirmasi>
                )
            }
        >
            <form onSubmit={kirim} className="space-y-6">
                <KartuAdmin judul="Identitas subbab">
                    <div className="grid gap-4 md:grid-cols-[10rem_1fr]">
                        <BidangTeks
                            id="kode_subbab"
                            label="Kode"
                            wajib
                            nilai={form.data.kode_subbab}
                            onUbah={(v) => form.setData('kode_subbab', v)}
                            galat={form.errors.kode_subbab}
                            maks={20}
                        />
                        <BidangTeks
                            id="judul"
                            label="Judul"
                            wajib
                            nilai={form.data.judul}
                            onUbah={(v) => {
                                form.setData((d) => ({ ...d, judul: v, slug: baru || !d.slug ? keSlug(v) : d.slug }));
                            }}
                            galat={form.errors.judul}
                        />
                        <BidangPilih
                            id="status"
                            label="Status"
                            wajib
                            nilai={form.data.status}
                            onUbah={(v) => form.setData('status', v)}
                            opsi={[
                                { nilai: 'draf', label: 'Draf' },
                                { nilai: 'terbit', label: 'Terbit' },
                                { nilai: 'arsip', label: 'Arsip' },
                            ]}
                            galat={form.errors.status}
                        />
                        <BidangTeks
                            id="slug"
                            label="Slug (jangkar)"
                            wajib
                            nilai={form.data.slug}
                            onUbah={(v) => form.setData('slug', v)}
                            galat={form.errors.slug}
                            petunjuk={`Jangkar pada halaman bab: #${form.data.slug}`}
                        />
                    </div>
                    <BidangTextarea
                        id="ringkasan"
                        className="mt-4"
                        label="Ringkasan"
                        baris={2}
                        nilai={form.data.ringkasan}
                        onUbah={(v) => form.setData('ringkasan', v)}
                        galat={form.errors.ringkasan}
                        maks={1000}
                    />
                </KartuAdmin>
                <KartuAdmin judul="Isi materi">
                    <div className="grid gap-1.5">
                        <Label htmlFor="isi-editor">
                            Isi <span className="text-destructive">*</span>
                        </Label>
                        <EditorTeks id="isi-editor" label="Isi subbab" nilai={form.data.isi} onUbah={(v) => form.setData('isi', v)} galat={form.errors.isi} />
                        {form.errors.isi && <p className="text-sm text-destructive">{form.errors.isi}</p>}
                    </div>
                    <div className="mt-5 grid gap-1.5">
                        <Label htmlFor="contoh-editor">Contoh praktik (opsional)</Label>
                        <EditorTeks
                            id="contoh-editor"
                            label="Contoh praktik"
                            nilai={form.data.contoh_praktik}
                            onUbah={(v) => form.setData('contoh_praktik', v)}
                            galat={form.errors.contoh_praktik}
                        />
                    </div>
                    <div className="mt-5 max-w-lg">
                        <BidangMedia
                            id="media"
                            label="Media pendukung (gambar/video)"
                            jenis={undefined}
                            media={media}
                            onUbah={(m) => {
                                setMedia(m);
                                form.setData('media_id', m?.id ?? null);
                            }}
                            galat={form.errors.media_id}
                        />
                    </div>
                </KartuAdmin>
                {subbab?.metadata_sumber && (
                    <KartuAdmin judul="Provenance sumber" deskripsi="Metadata internal (halaman PDF sumber, tabel/gambar yang dirujuk).">
                        <pre className="overflow-x-auto rounded-lg bg-muted p-3 text-xs">{JSON.stringify(subbab.metadata_sumber, null, 2)}</pre>
                    </KartuAdmin>
                )}
                <div className="sticky bottom-4 z-10 flex justify-end gap-2">
                    <Button asChild variant="outline" className="bg-background">
                        <Link href={`/admin/bab/${bab.id}/edit`}>Kembali</Link>
                    </Button>
                    <Button type="submit" disabled={form.processing} className="shadow-lg">
                        <Save /> {form.processing ? 'Menyimpan…' : 'Simpan subbab'}
                    </Button>
                </div>
            </form>
        </HalamanAdmin>
    );
}
