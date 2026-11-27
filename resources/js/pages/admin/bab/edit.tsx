import { Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Eye, FilePlus2, Pencil, Plus, Save } from 'lucide-react';
import { useState } from 'react';
import { BidangDaftar, BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { LencanaStatus } from '@/components/admin/daftar';
import { EditorTeks } from '@/components/admin/editor-teks';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { TombolKonfirmasi } from '@/components/admin/konfirmasi';
import { BidangMedia } from '@/components/admin/pemilih-media';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { formatTanggal } from '@/lib/utils';

type MediaRingkas = { id: number; url: string | null; judul: string } | null;
type Props = {
    bab: {
        id: number;
        nomor_bab: number;
        nomor_romawi: string;
        judul: string;
        slug: string;
        ringkasan: string | null;
        pengantar: string | null;
        judul_seo: string | null;
        deskripsi_seo: string | null;
        gambar_sampul_id: number | null;
        gambar_og_id: number | null;
        metadata_sumber: Record<string, unknown> | null;
        status: string;
        tujuan_pembelajaran: string[];
        poin_penting: string[];
        gambar_sampul: MediaRingkas;
        gambar_og: MediaRingkas;
        diterbitkan_pada: string | null;
        diperbarui_pada: string | null;
        waktu_baca_menit: number;
    };
    subbab: { id: number; kode: string; judul: string; slug: string; status: string; urutan: number; diperbarui_pada: string | null }[];
    dokumen: { id: number; nomor_versi: string; judul: string; status: string; aktif: boolean }[];
    riwayat: { id: number; aksi: string; ringkasan: string; pengguna: string | null; dibuat_pada: string | null }[];
};

export default function EditBab({ bab, subbab, dokumen, riwayat }: Props) {
    const [sampul, setSampul] = useState<MediaRingkas>(bab.gambar_sampul);
    const [og, setOg] = useState<MediaRingkas>(bab.gambar_og);
    const form = useForm({
        judul: bab.judul,
        slug: bab.slug,
        ringkasan: bab.ringkasan ?? '',
        pengantar: bab.pengantar ?? '',
        tujuan_pembelajaran: bab.tujuan_pembelajaran,
        poin_penting: bab.poin_penting,
        gambar_sampul_id: bab.gambar_sampul_id,
        gambar_og_id: bab.gambar_og_id,
        judul_seo: bab.judul_seo ?? '',
        deskripsi_seo: bab.deskripsi_seo ?? '',
    });
    const [urutan, setUrutan] = useState(subbab.map((s) => s.id));
    const urutanBerubah = urutan.join(',') !== subbab.map((s) => s.id).join(',');
    const geser = (i: number, arah: -1 | 1) => {
        const baru = [...urutan];
        [baru[i], baru[i + arah]] = [baru[i + arah], baru[i]];
        setUrutan(baru);
    };
    const peta = Object.fromEntries(subbab.map((s) => [s.id, s]));

    return (
        <HalamanAdmin
            judul={`Bab ${bab.nomor_romawi} · ${bab.judul}`}
            jejak={[{ judul: 'Bab & Subbab', href: '/admin/bab' }, { judul: `Bab ${bab.nomor_romawi}` }]}
            deskripsi={
                <>
                    <LencanaStatus status={bab.status} /> · ±{bab.waktu_baca_menit} menit baca (dihitung dari materi terbit) · diperbarui{' '}
                    {formatTanggal(bab.diperbarui_pada, true)}
                </>
            }
            aksi={
                <>
                    <Button asChild variant="outline" size="sm">
                        <Link href={`/admin/bab/${bab.id}/pratinjau`}>
                            <Eye /> Pratinjau
                        </Link>
                    </Button>
                    {bab.status !== 'terbit' && (
                        <TombolKonfirmasi
                            url={`/admin/bab/${bab.id}/status`}
                            metode="post"
                            data={{ status: 'terbit' }}
                            varian="default"
                            labelAksi="Terbitkan"
                            judul="Terbitkan bab?"
                            deskripsi="Bab akan tampil di website, pencarian, dan sitemap."
                        >
                            Terbitkan
                        </TombolKonfirmasi>
                    )}
                    {bab.status === 'terbit' && (
                        <TombolKonfirmasi
                            url={`/admin/bab/${bab.id}/status`}
                            metode="post"
                            data={{ status: 'draf' }}
                            varian="outline"
                            labelAksi="Jadikan draf"
                            judul="Jadikan draf?"
                            deskripsi="Bab akan disembunyikan dari publik, pencarian, sitemap, dan navigasi."
                        >
                            Jadikan draf
                        </TombolKonfirmasi>
                    )}
                    {bab.status !== 'arsip' && (
                        <TombolKonfirmasi
                            url={`/admin/bab/${bab.id}/status`}
                            metode="post"
                            data={{ status: 'arsip' }}
                            labelAksi="Arsipkan"
                            judul="Arsipkan bab?"
                            deskripsi="Bab, subbab, dan dokumen bab tidak dapat diakses publik selama diarsipkan. Struktur bab tetap tersimpan."
                        >
                            Arsipkan
                        </TombolKonfirmasi>
                    )}
                </>
            }
        >
            <div className="grid gap-6 xl:grid-cols-[1fr_22rem]">
                <form
                    className="space-y-6"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put(`/admin/bab/${bab.id}`, { preserveScroll: true });
                    }}
                >
                    <KartuAdmin judul="Informasi bab">
                        <div className="grid gap-4">
                            <BidangTeks
                                id="judul"
                                label="Judul"
                                wajib
                                nilai={form.data.judul}
                                onUbah={(v) => form.setData('judul', v)}
                                galat={form.errors.judul}
                            />
                            <BidangTeks
                                id="slug"
                                label="Slug URL"
                                wajib
                                nilai={form.data.slug}
                                onUbah={(v) => form.setData('slug', v)}
                                galat={form.errors.slug}
                                petunjuk={`URL: /modul/${form.data.slug}. Mengubah slug membuat pengalihan 301 otomatis dari URL lama.`}
                            />
                            <BidangTextarea
                                id="ringkasan"
                                label="Ringkasan"
                                wajib
                                baris={3}
                                nilai={form.data.ringkasan}
                                onUbah={(v) => form.setData('ringkasan', v)}
                                galat={form.errors.ringkasan}
                                maks={1000}
                            />
                            <BidangDaftar
                                id="tujuan"
                                label="Tujuan belajar"
                                nilai={form.data.tujuan_pembelajaran}
                                onUbah={(v) => form.setData('tujuan_pembelajaran', v)}
                                galat={form.errors.tujuan_pembelajaran}
                                placeholder="Tambah tujuan"
                            />
                            <BidangDaftar
                                id="poin"
                                label="Ringkasan & hal yang perlu diperhatikan"
                                nilai={form.data.poin_penting}
                                onUbah={(v) => form.setData('poin_penting', v)}
                                galat={form.errors.poin_penting}
                                placeholder="Tambah poin"
                            />
                            <div className="grid gap-1.5">
                                <Label htmlFor="pengantar-editor">Pengantar bab (opsional)</Label>
                                <EditorTeks
                                    id="pengantar-editor"
                                    label="Pengantar bab"
                                    nilai={form.data.pengantar}
                                    onUbah={(v) => form.setData('pengantar', v)}
                                    galat={form.errors.pengantar}
                                />
                            </div>
                        </div>
                    </KartuAdmin>
                    <KartuAdmin judul="Media & SEO">
                        <div className="grid gap-4 md:grid-cols-2">
                            <BidangMedia
                                id="gambar_sampul"
                                label="Gambar sampul"
                                media={sampul}
                                onUbah={(m) => {
                                    setSampul(m);
                                    form.setData('gambar_sampul_id', m?.id ?? null);
                                }}
                                galat={form.errors.gambar_sampul_id}
                            />
                            <BidangMedia
                                id="gambar_og"
                                label="Gambar Open Graph"
                                media={og}
                                onUbah={(m) => {
                                    setOg(m);
                                    form.setData('gambar_og_id', m?.id ?? null);
                                }}
                                galat={form.errors.gambar_og_id}
                                petunjuk="Disarankan 1200×630 piksel."
                            />
                            <BidangTeks
                                id="judul_seo"
                                label="Judul SEO"
                                nilai={form.data.judul_seo}
                                onUbah={(v) => form.setData('judul_seo', v)}
                                galat={form.errors.judul_seo}
                                maks={191}
                            />
                            <BidangTextarea
                                id="deskripsi_seo"
                                label="Deskripsi SEO"
                                baris={2}
                                nilai={form.data.deskripsi_seo}
                                onUbah={(v) => form.setData('deskripsi_seo', v)}
                                galat={form.errors.deskripsi_seo}
                                maks={300}
                                petunjuk={`${form.data.deskripsi_seo.length}/300 karakter`}
                            />
                        </div>
                    </KartuAdmin>
                    <div className="sticky bottom-4 z-10 flex justify-end">
                        <Button type="submit" disabled={form.processing} className="shadow-lg">
                            <Save /> {form.processing ? 'Menyimpan…' : 'Simpan bab'}
                        </Button>
                    </div>
                </form>

                <div className="space-y-6">
                    <KartuAdmin
                        judul="Subbab"
                        aksi={
                            <Button asChild size="sm">
                                <Link href={`/admin/bab/${bab.id}/subbab/buat`}>
                                    <Plus /> Tambah
                                </Link>
                            </Button>
                        }
                    >
                        <ol className="space-y-2">
                            {urutan.map((id, i) => {
                                const s = peta[id];

                                return (
                                    <li key={id} className="flex items-center gap-2 rounded-lg border p-2">
                                        <div className="flex flex-col">
                                            <button
                                                type="button"
                                                disabled={i === 0}
                                                onClick={() => geser(i, -1)}
                                                aria-label={`Naikkan ${s.judul}`}
                                                className="rounded p-0.5 hover:bg-accent disabled:opacity-30"
                                            >
                                                <ArrowUp className="size-3.5" />
                                            </button>
                                            <button
                                                type="button"
                                                disabled={i === urutan.length - 1}
                                                onClick={() => geser(i, 1)}
                                                aria-label={`Turunkan ${s.judul}`}
                                                className="rounded p-0.5 hover:bg-accent disabled:opacity-30"
                                            >
                                                <ArrowDown className="size-3.5" />
                                            </button>
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-semibold">
                                                <span className="mr-1 font-mono text-xs text-muted-foreground">{s.kode}</span>
                                                {s.judul}
                                            </p>
                                            <LencanaStatus status={s.status} />
                                        </div>
                                        <Button asChild variant="ghost" size="icon" aria-label={`Sunting ${s.judul}`}>
                                            <Link href={`/admin/bab/${bab.id}/subbab/${s.id}/edit`}>
                                                <Pencil />
                                            </Link>
                                        </Button>
                                    </li>
                                );
                            })}
                        </ol>
                        {urutanBerubah && (
                            <div className="mt-3 flex gap-2">
                                <Button size="sm" onClick={() => router.post(`/admin/bab/${bab.id}/subbab/urutan`, { id: urutan }, { preserveScroll: true })}>
                                    Simpan urutan
                                </Button>
                                <Button size="sm" variant="ghost" onClick={() => setUrutan(subbab.map((s) => s.id))}>
                                    Batal
                                </Button>
                            </div>
                        )}
                    </KartuAdmin>
                    <KartuAdmin
                        judul="Dokumen PDF bab"
                        aksi={
                            <Button asChild size="sm" variant="outline">
                                <Link href={`/admin/dokumen/unggah?bab=${bab.id}`}>
                                    <FilePlus2 /> Unggah versi
                                </Link>
                            </Button>
                        }
                    >
                        {dokumen.length ? (
                            <ul className="space-y-2 text-sm">
                                {dokumen.map((d) => (
                                    <li key={d.id} className="flex items-center justify-between gap-2">
                                        <Link href={`/admin/dokumen/${d.id}`} className="hover:text-primary">
                                            v{d.nomor_versi} {d.aktif && <strong>(aktif)</strong>}
                                        </Link>
                                        <LencanaStatus status={d.status} />
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-muted-foreground">Belum ada dokumen. Bab tetap dapat terbit dengan materi HTML.</p>
                        )}
                    </KartuAdmin>
                    {bab.metadata_sumber && (
                        <KartuAdmin judul="Provenance sumber" deskripsi="Metadata internal, tidak tampil publik.">
                            <pre className="overflow-x-auto rounded-lg bg-muted p-3 text-xs">{JSON.stringify(bab.metadata_sumber, null, 2)}</pre>
                        </KartuAdmin>
                    )}
                    <KartuAdmin judul="Riwayat pembaruan">
                        {riwayat.length ? (
                            <ol className="space-y-2 text-sm">
                                {riwayat.map((r) => (
                                    <li key={r.id}>
                                        <p>{r.ringkasan}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {r.pengguna ?? 'Sistem'} · {formatTanggal(r.dibuat_pada, true)}
                                        </p>
                                    </li>
                                ))}
                            </ol>
                        ) : (
                            <p className="text-sm text-muted-foreground">Belum ada riwayat.</p>
                        )}
                    </KartuAdmin>
                </div>
            </div>
        </HalamanAdmin>
    );
}
