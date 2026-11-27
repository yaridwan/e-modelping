import { router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { BidangDaftar, BidangPilih, BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { TombolKonfirmasi } from '@/components/admin/konfirmasi';
import { BidangMedia } from '@/components/admin/pemilih-media';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Dialog } from '@/types';

type MediaRingkas = { id: number; url: string | null; judul: string } | null;
type Langkah = {
    id: number;
    nomor_langkah: number;
    judul: string;
    penjelasan: string;
    aktivitas_guru: string | null;
    aktivitas_anak: string | null;
    contoh_dialog: Dialog[];
    media_id: number | null;
    media: MediaRingkas;
};
type Props = {
    fase: {
        id: number;
        kode_fase: string;
        nama: string;
        slug: string;
        judul_indonesia: string;
        deskripsi: string;
        waktu_pelaksanaan: string | null;
        durasi_minimum_menit: number | null;
        durasi_maksimum_menit: number | null;
        frekuensi_panduan: string | null;
        catatan_guru: string | null;
        media_id: number | null;
        status: string;
        tujuan: string[];
        indikator_keberhasilan: string[];
        penguatan_karakter: { aspek: string; bentuk: string }[];
        media: MediaRingkas;
    };
    langkah: Langkah[];
};

function EditorDialog({ nilai, onUbah }: { nilai: Dialog[]; onUbah: (d: Dialog[]) => void }) {
    return (
        <fieldset className="grid gap-2">
            <legend className="mb-1 text-sm font-medium">Contoh dialog</legend>
            {nilai.map((d, i) => (
                <div key={i} className="grid gap-2 sm:grid-cols-[8rem_1fr_auto]">
                    <Input
                        aria-label={`Pembicara ${i + 1}`}
                        value={d.pembicara}
                        onChange={(e) => onUbah(nilai.map((x, j) => (j === i ? { ...x, pembicara: e.target.value } : x)))}
                        placeholder="Guru/Anak"
                    />
                    <Input
                        aria-label={`Ucapan ${i + 1}`}
                        value={d.ucapan}
                        onChange={(e) => onUbah(nilai.map((x, j) => (j === i ? { ...x, ucapan: e.target.value } : x)))}
                        placeholder="Ucapan"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => onUbah(nilai.filter((_, j) => j !== i))}
                        aria-label={`Hapus dialog ${i + 1}`}
                    >
                        <Trash2 />
                    </Button>
                </div>
            ))}
            <Button type="button" variant="outline" size="sm" className="w-fit" onClick={() => onUbah([...nilai, { pembicara: 'Guru', ucapan: '' }])}>
                <Plus /> Tambah ucapan
            </Button>
        </fieldset>
    );
}

function FormLangkah({ faseId, langkah, onSelesai }: { faseId: number; langkah?: Langkah; onSelesai?: () => void }) {
    const [media, setMedia] = useState<MediaRingkas>(langkah?.media ?? null);
    const form = useForm({
        judul: langkah?.judul ?? '',
        penjelasan: langkah?.penjelasan ?? '',
        aktivitas_guru: langkah?.aktivitas_guru ?? '',
        aktivitas_anak: langkah?.aktivitas_anak ?? '',
        contoh_dialog: langkah?.contoh_dialog ?? [],
        media_id: langkah?.media_id ?? null,
    });

    return (
        <form
            className="grid gap-3"
            onSubmit={(e) => {
                e.preventDefault();
                const opsi = {
                    preserveScroll: true,
                    onSuccess: () => {
                        if (!langkah) {
                            form.reset();
                        }
                        onSelesai?.();
                    },
                };
                if (langkah) {
                    form.put(`/admin/fase/${faseId}/langkah/${langkah.id}`, opsi);
                } else {
                    form.post(`/admin/fase/${faseId}/langkah`, opsi);
                }
            }}
        >
            <BidangTeks
                id={`judul-${langkah?.id ?? 'baru'}`}
                label="Judul langkah"
                wajib
                nilai={form.data.judul}
                onUbah={(v) => form.setData('judul', v)}
                galat={form.errors.judul}
            />
            <BidangTextarea
                id={`penjelasan-${langkah?.id ?? 'baru'}`}
                label="Penjelasan"
                wajib
                baris={3}
                nilai={form.data.penjelasan}
                onUbah={(v) => form.setData('penjelasan', v)}
                galat={form.errors.penjelasan}
            />
            <div className="grid gap-3 md:grid-cols-2">
                <BidangTextarea
                    id={`guru-${langkah?.id ?? 'baru'}`}
                    label="Aktivitas guru"
                    baris={2}
                    nilai={form.data.aktivitas_guru}
                    onUbah={(v) => form.setData('aktivitas_guru', v)}
                    galat={form.errors.aktivitas_guru}
                />
                <BidangTextarea
                    id={`anak-${langkah?.id ?? 'baru'}`}
                    label="Aktivitas anak"
                    baris={2}
                    nilai={form.data.aktivitas_anak}
                    onUbah={(v) => form.setData('aktivitas_anak', v)}
                    galat={form.errors.aktivitas_anak}
                />
            </div>
            <EditorDialog nilai={form.data.contoh_dialog} onUbah={(d) => form.setData('contoh_dialog', d)} />
            <BidangMedia
                id={`media-${langkah?.id ?? 'baru'}`}
                label="Media langkah"
                jenis={undefined}
                media={media}
                onUbah={(m) => {
                    setMedia(m);
                    form.setData('media_id', m?.id ?? null);
                }}
            />
            <Button type="submit" size="sm" className="w-fit" disabled={form.processing}>
                <Save /> {langkah ? 'Simpan langkah' : 'Tambah langkah'}
            </Button>
        </form>
    );
}

export default function EditFase({ fase, langkah }: Props) {
    const [media, setMedia] = useState<MediaRingkas>(fase.media);
    const [urutan, setUrutan] = useState(langkah.map((l) => l.id));
    const form = useForm({
        nama: fase.nama,
        judul_indonesia: fase.judul_indonesia,
        deskripsi: fase.deskripsi,
        tujuan: fase.tujuan,
        waktu_pelaksanaan: fase.waktu_pelaksanaan ?? '',
        durasi_minimum_menit: fase.durasi_minimum_menit ?? '',
        durasi_maksimum_menit: fase.durasi_maksimum_menit ?? '',
        frekuensi_panduan: fase.frekuensi_panduan ?? '',
        media_id: fase.media_id,
        catatan_guru: fase.catatan_guru ?? '',
        indikator_keberhasilan: fase.indikator_keberhasilan,
        penguatan_karakter: fase.penguatan_karakter,
        status: fase.status,
    });
    const peta = Object.fromEntries(langkah.map((l) => [l.id, l]));
    const geser = (i: number, arah: -1 | 1) => {
        const b = [...urutan];
        [b[i], b[i + arah]] = [b[i + arah], b[i]];
        setUrutan(b);
    };

    return (
        <HalamanAdmin
            judul={`Sunting ${fase.nama}`}
            jejak={[{ judul: 'Fase PING', href: '/admin/fase' }, { judul: fase.nama }]}
            deskripsi={`URL publik: /fase/${fase.slug}`}
        >
            <form
                className="space-y-6"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.put(`/admin/fase/${fase.id}`, { preserveScroll: true });
                }}
            >
                <KartuAdmin judul="Informasi fase">
                    <div className="grid gap-4 md:grid-cols-2">
                        <BidangTeks id="nama" label="Nama fase" wajib nilai={form.data.nama} onUbah={(v) => form.setData('nama', v)} galat={form.errors.nama} />
                        <BidangTeks
                            id="judul_indonesia"
                            label="Judul Indonesia"
                            wajib
                            nilai={form.data.judul_indonesia}
                            onUbah={(v) => form.setData('judul_indonesia', v)}
                            galat={form.errors.judul_indonesia}
                        />
                        <BidangTextarea
                            id="deskripsi"
                            label="Deskripsi"
                            wajib
                            className="md:col-span-2"
                            nilai={form.data.deskripsi}
                            onUbah={(v) => form.setData('deskripsi', v)}
                            galat={form.errors.deskripsi}
                        />
                        <BidangTeks
                            id="durasi_min"
                            label="Durasi minimum (menit)"
                            tipe="number"
                            nilai={form.data.durasi_minimum_menit}
                            onUbah={(v) => form.setData('durasi_minimum_menit', v)}
                            galat={form.errors.durasi_minimum_menit}
                        />
                        <BidangTeks
                            id="durasi_maks"
                            label="Durasi maksimum (menit)"
                            tipe="number"
                            nilai={form.data.durasi_maksimum_menit}
                            onUbah={(v) => form.setData('durasi_maksimum_menit', v)}
                            galat={form.errors.durasi_maksimum_menit}
                        />
                        <BidangTeks
                            id="frekuensi"
                            label="Frekuensi panduan"
                            nilai={form.data.frekuensi_panduan}
                            onUbah={(v) => form.setData('frekuensi_panduan', v)}
                            galat={form.errors.frekuensi_panduan}
                        />
                        <BidangPilih
                            id="status"
                            label="Status"
                            nilai={form.data.status}
                            onUbah={(v) => form.setData('status', v)}
                            opsi={[
                                { nilai: 'terbit', label: 'Terbit' },
                                { nilai: 'draf', label: 'Draf' },
                                { nilai: 'arsip', label: 'Arsip' },
                            ]}
                        />
                        <BidangTextarea
                            id="waktu"
                            label="Waktu pelaksanaan"
                            className="md:col-span-2"
                            baris={2}
                            nilai={form.data.waktu_pelaksanaan}
                            onUbah={(v) => form.setData('waktu_pelaksanaan', v)}
                            galat={form.errors.waktu_pelaksanaan}
                        />
                        <BidangDaftar
                            id="tujuan"
                            label="Tujuan fase"
                            nilai={form.data.tujuan}
                            onUbah={(v) => form.setData('tujuan', v)}
                            galat={form.errors.tujuan}
                        />
                        <BidangDaftar
                            id="keberhasilan"
                            label="Indikator keberhasilan"
                            nilai={form.data.indikator_keberhasilan}
                            onUbah={(v) => form.setData('indikator_keberhasilan', v)}
                            galat={form.errors.indikator_keberhasilan}
                        />
                        <BidangTextarea
                            id="catatan_guru"
                            label="Catatan guru"
                            className="md:col-span-2"
                            nilai={form.data.catatan_guru}
                            onUbah={(v) => form.setData('catatan_guru', v)}
                            galat={form.errors.catatan_guru}
                        />
                        <fieldset className="grid gap-2 md:col-span-2">
                            <legend className="mb-1 text-sm font-medium">Penguatan karakter per aspek</legend>
                            {form.data.penguatan_karakter.map((p, i) => (
                                <div key={i} className="grid gap-2 sm:grid-cols-[10rem_1fr_auto]">
                                    <Input
                                        aria-label={`Aspek ${i + 1}`}
                                        value={p.aspek}
                                        onChange={(e) =>
                                            form.setData(
                                                'penguatan_karakter',
                                                form.data.penguatan_karakter.map((x, j) => (j === i ? { ...x, aspek: e.target.value } : x)),
                                            )
                                        }
                                    />
                                    <Input
                                        aria-label={`Bentuk penguatan ${i + 1}`}
                                        value={p.bentuk}
                                        onChange={(e) =>
                                            form.setData(
                                                'penguatan_karakter',
                                                form.data.penguatan_karakter.map((x, j) => (j === i ? { ...x, bentuk: e.target.value } : x)),
                                            )
                                        }
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Hapus baris ${i + 1}`}
                                        onClick={() =>
                                            form.setData(
                                                'penguatan_karakter',
                                                form.data.penguatan_karakter.filter((_, j) => j !== i),
                                            )
                                        }
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            ))}
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="w-fit"
                                onClick={() => form.setData('penguatan_karakter', [...form.data.penguatan_karakter, { aspek: '', bentuk: '' }])}
                            >
                                <Plus /> Tambah baris
                            </Button>
                        </fieldset>
                        <BidangMedia
                            id="media-fase"
                            label="Media fase"
                            jenis={undefined}
                            media={media}
                            onUbah={(m) => {
                                setMedia(m);
                                form.setData('media_id', m?.id ?? null);
                            }}
                            galat={form.errors.media_id}
                        />
                    </div>
                    <Button type="submit" className="mt-5" disabled={form.processing}>
                        <Save /> Simpan fase
                    </Button>
                </KartuAdmin>
            </form>

            <KartuAdmin className="mt-6" judul={`Langkah (${langkah.length})`} deskripsi="Nomor langkah mengikuti urutan.">
                <ol className="space-y-4">
                    {urutan.map((id, i) => {
                        const l = peta[id];

                        return (
                            <li key={id} className="rounded-xl border p-4">
                                <div className="mb-3 flex items-center gap-2">
                                    <span className="flex size-7 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                                        {i + 1}
                                    </span>
                                    <span className="font-semibold">{l.judul}</span>
                                    <span className="ml-auto flex gap-1">
                                        <Button type="button" variant="ghost" size="icon" disabled={i === 0} onClick={() => geser(i, -1)} aria-label="Naikkan">
                                            <ArrowUp />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            disabled={i === urutan.length - 1}
                                            onClick={() => geser(i, 1)}
                                            aria-label="Turunkan"
                                        >
                                            <ArrowDown />
                                        </Button>
                                        <TombolKonfirmasi
                                            url={`/admin/fase/${fase.id}/langkah/${l.id}`}
                                            ukuran="sm"
                                            judul="Hapus langkah?"
                                            deskripsi="Langkah dihapus dan nomor langkah lain dirapatkan."
                                        >
                                            <Trash2 />
                                        </TombolKonfirmasi>
                                    </span>
                                </div>
                                <details>
                                    <summary className="cursor-pointer text-sm text-primary">Sunting langkah</summary>
                                    <div className="mt-3">
                                        <FormLangkah faseId={fase.id} langkah={l} />
                                    </div>
                                </details>
                            </li>
                        );
                    })}
                </ol>
                {urutan.join(',') !== langkah.map((l) => l.id).join(',') && (
                    <Button
                        size="sm"
                        className="mt-3"
                        onClick={() => router.post(`/admin/fase/${fase.id}/langkah/urutan`, { id: urutan }, { preserveScroll: true })}
                    >
                        Simpan urutan langkah
                    </Button>
                )}
                <div className="mt-6 rounded-xl border border-dashed p-4">
                    <p className="mb-3 font-semibold">Tambah langkah baru</p>
                    <FormLangkah faseId={fase.id} />
                </div>
            </KartuAdmin>
        </HalamanAdmin>
    );
}
