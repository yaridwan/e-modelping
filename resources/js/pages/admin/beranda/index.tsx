import { router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Save } from 'lucide-react';
import { useState } from 'react';
import { BidangDaftar, BidangPilih, BidangSakelar, BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { BidangMedia } from '@/components/admin/pemilih-media';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Skema = Record<string, { label: string; jenis: 'boolean' | 'angka' | 'pilihan' | 'daftar'; opsi?: Record<string, string>; bawaan: unknown }>;
type NilaiTampilan = string | number | boolean | string[];
type Bagian = {
    id: number;
    kode_bagian: string;
    jenis_bagian: string;
    judul: string;
    subjudul: string | null;
    ringkasan: string | null;
    isi: string | null;
    teks_tombol_utama: string | null;
    tautan_tombol_utama: string | null;
    teks_tombol_pendamping: string | null;
    tautan_tombol_pendamping: string | null;
    media_id: number | null;
    urutan: number;
    aktif: boolean;
    pengaturan_tampilan: Record<string, NilaiTampilan>;
    skema: Skema;
    media: { id: number; url: string | null; judul: string } | null;
};

const LABEL_JENIS: Record<string, string> = {
    hero: 'Hero',
    pengantar: 'Pengantar PING',
    fase: 'Tiga fase',
    aspek: 'Empat aspek',
    bab: 'Grid sembilan bab',
    panduan: 'Panduan penggunaan',
    profil: 'Profil penyusun',
    galeri: 'Galeri',
    faq: 'FAQ',
    cta: 'CTA akhir',
};

function FormBagian({ b }: { b: Bagian }) {
    const [media, setMedia] = useState(b.media);
    const form = useForm({
        judul: b.judul,
        subjudul: b.subjudul ?? '',
        ringkasan: b.ringkasan ?? '',
        isi: b.isi ?? '',
        teks_tombol_utama: b.teks_tombol_utama ?? '',
        tautan_tombol_utama: b.tautan_tombol_utama ?? '',
        teks_tombol_pendamping: b.teks_tombol_pendamping ?? '',
        tautan_tombol_pendamping: b.tautan_tombol_pendamping ?? '',
        media_id: b.media_id,
        aktif: b.aktif,
        pengaturan_tampilan: b.pengaturan_tampilan,
    });
    const pt = form.data.pengaturan_tampilan;
    const ubahPt = (k: string, v: NilaiTampilan) => form.setData('pengaturan_tampilan', { ...pt, [k]: v });
    const err = form.errors as Record<string, string>;

    return (
        <form
            className="grid gap-4 md:grid-cols-2"
            onSubmit={(e) => {
                e.preventDefault();
                form.put(`/admin/beranda/${b.id}`, { preserveScroll: true });
            }}
        >
            <BidangSakelar id={`aktif-${b.id}`} label="Tampilkan bagian ini" nilai={form.data.aktif} onUbah={(v) => form.setData('aktif', v)} />
            <span />
            <BidangTeks id={`judul-${b.id}`} label="Judul" wajib nilai={form.data.judul} onUbah={(v) => form.setData('judul', v)} galat={form.errors.judul} />
            <BidangTeks
                id={`subjudul-${b.id}`}
                label="Subjudul / label kecil"
                nilai={form.data.subjudul}
                onUbah={(v) => form.setData('subjudul', v)}
                galat={form.errors.subjudul}
            />
            <BidangTextarea
                id={`ringkasan-${b.id}`}
                label="Ringkasan"
                className="md:col-span-2"
                baris={3}
                nilai={form.data.ringkasan}
                onUbah={(v) => form.setData('ringkasan', v)}
                galat={form.errors.ringkasan}
            />
            <BidangTeks
                id={`tu-${b.id}`}
                label="Teks tombol utama"
                nilai={form.data.teks_tombol_utama}
                onUbah={(v) => form.setData('teks_tombol_utama', v)}
                galat={form.errors.teks_tombol_utama}
            />
            <BidangTeks
                id={`lu-${b.id}`}
                label="Tautan tombol utama"
                placeholder="/modul atau https://…"
                nilai={form.data.tautan_tombol_utama}
                onUbah={(v) => form.setData('tautan_tombol_utama', v)}
                galat={form.errors.tautan_tombol_utama}
            />
            <BidangTeks
                id={`tp-${b.id}`}
                label="Teks tombol pendamping"
                nilai={form.data.teks_tombol_pendamping}
                onUbah={(v) => form.setData('teks_tombol_pendamping', v)}
                galat={form.errors.teks_tombol_pendamping}
            />
            <BidangTeks
                id={`lp-${b.id}`}
                label="Tautan tombol pendamping"
                nilai={form.data.tautan_tombol_pendamping}
                onUbah={(v) => form.setData('tautan_tombol_pendamping', v)}
                galat={form.errors.tautan_tombol_pendamping}
            />
            {b.jenis_bagian === 'hero' && (
                <BidangMedia
                    id={`media-${b.id}`}
                    label="Gambar hero (opsional, menggantikan ilustrasi)"
                    media={media}
                    onUbah={(m) => {
                        setMedia(m);
                        form.setData('media_id', m?.id ?? null);
                    }}
                    galat={form.errors.media_id}
                />
            )}
            {Object.entries(b.skema).map(([k, d]) => {
                const id = `pt-${b.id}-${k}`;
                if (d.jenis === 'boolean') {
                    return (
                        <BidangSakelar
                            key={k}
                            id={id}
                            label={d.label}
                            nilai={Boolean(pt[k])}
                            onUbah={(v) => ubahPt(k, v)}
                            galat={err[`pengaturan_tampilan.${k}`]}
                        />
                    );
                }
                if (d.jenis === 'pilihan') {
                    return (
                        <BidangPilih
                            key={k}
                            id={id}
                            label={d.label}
                            nilai={String(pt[k] ?? '')}
                            onUbah={(v) => ubahPt(k, v)}
                            opsi={Object.entries(d.opsi ?? {}).map(([n, l]) => ({ nilai: n, label: l }))}
                            galat={err[`pengaturan_tampilan.${k}`]}
                        />
                    );
                }
                if (d.jenis === 'angka') {
                    return (
                        <BidangTeks
                            key={k}
                            id={id}
                            label={d.label}
                            tipe="number"
                            nilai={Number(pt[k] ?? 0)}
                            onUbah={(v) => ubahPt(k, Number(v))}
                            galat={err[`pengaturan_tampilan.${k}`]}
                        />
                    );
                }

                return (
                    <div key={k} className="md:col-span-2">
                        <BidangDaftar
                            id={id}
                            label={d.label}
                            nilai={(pt[k] as string[]) ?? []}
                            onUbah={(v) => ubahPt(k, v)}
                            galat={err[`pengaturan_tampilan.${k}`]}
                        />
                    </div>
                );
            })}
            <div className="md:col-span-2">
                <Button type="submit" size="sm" disabled={form.processing}>
                    <Save /> Simpan bagian
                </Button>
            </div>
        </form>
    );
}

export default function KelolaBeranda({ bagian }: { bagian: Bagian[] }) {
    const [urutan, setUrutan] = useState(bagian.map((b) => b.id));
    const peta = Object.fromEntries(bagian.map((b) => [b.id, b]));
    const geser = (i: number, arah: -1 | 1) => {
        const x = [...urutan];
        [x[i], x[i + arah]] = [x[i + arah], x[i]];
        setUrutan(x);
    };

    return (
        <HalamanAdmin
            judul="Beranda Dinamis"
            jejak={[{ judul: 'Beranda' }]}
            deskripsi="Aktifkan, urutkan, dan sunting setiap bagian landing page. Data fase, bab, FAQ, dan galeri diambil dari tabel masing-masing."
            aksi={
                <Button asChild variant="outline">
                    <a href="/" target="_blank" rel="noopener">
                        Lihat beranda
                    </a>
                </Button>
            }
        >
            <KartuAdmin judul="Urutan bagian">
                <ol className="grid gap-2 sm:grid-cols-2">
                    {urutan.map((id, i) => (
                        <li key={id} className="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm">
                            <span className="w-6 text-muted-foreground">{i + 1}.</span>
                            <span className="flex-1 font-medium">{LABEL_JENIS[peta[id].jenis_bagian]}</span>
                            {!peta[id].aktif && <Badge variant="outline">Nonaktif</Badge>}
                            <Button variant="ghost" size="icon" disabled={i === 0} onClick={() => geser(i, -1)} aria-label="Naikkan">
                                <ArrowUp />
                            </Button>
                            <Button variant="ghost" size="icon" disabled={i === urutan.length - 1} onClick={() => geser(i, 1)} aria-label="Turunkan">
                                <ArrowDown />
                            </Button>
                        </li>
                    ))}
                </ol>
                {urutan.join(',') !== bagian.map((b) => b.id).join(',') && (
                    <Button size="sm" className="mt-3" onClick={() => router.post('/admin/beranda/urutan', { id: urutan }, { preserveScroll: true })}>
                        Simpan urutan
                    </Button>
                )}
            </KartuAdmin>
            <div className="mt-6 space-y-4">
                {bagian.map((b) => (
                    <details key={b.id} className="rounded-xl border bg-card">
                        <summary className="flex cursor-pointer items-center gap-2 px-5 py-4 font-semibold">
                            {LABEL_JENIS[b.jenis_bagian]} <span className="font-normal text-muted-foreground">· {b.judul}</span>
                            {!b.aktif && <Badge variant="outline">Nonaktif</Badge>}
                        </summary>
                        <div className="border-t p-5">
                            <FormBagian b={b} />
                        </div>
                    </details>
                ))}
            </div>
        </HalamanAdmin>
    );
}
