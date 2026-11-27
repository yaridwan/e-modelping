import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { BidangPilih, BidangSakelar, BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';

type Indikator = {
    id: number;
    nomor_indikator: number;
    rumusan: string;
    contoh_pengamatan: string | null;
    catatan_pendampingan: string | null;
    rubrik_bb: string | null;
    rubrik_mb: string | null;
    rubrik_bsh: string | null;
    rubrik_bsb: string | null;
    aktif: boolean;
    fase_pengamatan: string[];
};
type Aspek = {
    id: number;
    kode_aspek: string;
    nama: string;
    slug: string;
    deskripsi: string;
    contoh_pembiasaan: string | null;
    fokus_pengamatan: string | null;
    ikon: string | null;
    warna: string | null;
    aktif: boolean;
    indikator: Indikator[];
};

function FormIndikator({ i }: { i: Indikator }) {
    const form = useForm({
        rumusan: i.rumusan,
        contoh_pengamatan: i.contoh_pengamatan ?? '',
        fase_pengamatan: i.fase_pengamatan,
        catatan_pendampingan: i.catatan_pendampingan ?? '',
        rubrik_bb: i.rubrik_bb ?? '',
        rubrik_mb: i.rubrik_mb ?? '',
        rubrik_bsh: i.rubrik_bsh ?? '',
        rubrik_bsb: i.rubrik_bsb ?? '',
        aktif: i.aktif,
    });
    const fase = [
        ['PCG', 'Fase I (Pre-Class)'],
        ['ICTG', 'Fase II (Inter-Class)'],
        ['EDCG', 'Fase III (End-Day)'],
    ];

    return (
        <form
            className="grid gap-3"
            onSubmit={(e) => {
                e.preventDefault();
                form.put(`/admin/karakter/indikator/${i.id}`, { preserveScroll: true });
            }}
        >
            <BidangTeks
                id={`rumusan-${i.id}`}
                label="Rumusan indikator"
                wajib
                nilai={form.data.rumusan}
                onUbah={(v) => form.setData('rumusan', v)}
                galat={form.errors.rumusan}
            />
            <BidangTextarea
                id={`contoh-${i.id}`}
                label="Contoh pengamatan"
                nilai={form.data.contoh_pengamatan}
                onUbah={(v) => form.setData('contoh_pengamatan', v)}
                galat={form.errors.contoh_pengamatan}
            />
            <fieldset>
                <legend className="mb-2 text-sm font-medium">Fase pengamatan</legend>
                <div className="flex flex-wrap gap-4">
                    {fase.map(([kode, label]) => (
                        <label key={kode} className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={form.data.fase_pengamatan.includes(kode)}
                                onCheckedChange={(v) =>
                                    form.setData(
                                        'fase_pengamatan',
                                        v ? [...form.data.fase_pengamatan, kode] : form.data.fase_pengamatan.filter((f) => f !== kode),
                                    )
                                }
                            />
                            {label}
                        </label>
                    ))}
                </div>
            </fieldset>
            <div className="grid gap-3 md:grid-cols-2">
                <BidangTextarea
                    id={`bsb-${i.id}`}
                    label="Rubrik BSB (4)"
                    baris={2}
                    nilai={form.data.rubrik_bsb}
                    onUbah={(v) => form.setData('rubrik_bsb', v)}
                />
                <BidangTextarea
                    id={`bsh-${i.id}`}
                    label="Rubrik BSH (3)"
                    baris={2}
                    nilai={form.data.rubrik_bsh}
                    onUbah={(v) => form.setData('rubrik_bsh', v)}
                />
                <BidangTextarea id={`mb-${i.id}`} label="Rubrik MB (2)" baris={2} nilai={form.data.rubrik_mb} onUbah={(v) => form.setData('rubrik_mb', v)} />
                <BidangTextarea id={`bb-${i.id}`} label="Rubrik BB (1)" baris={2} nilai={form.data.rubrik_bb} onUbah={(v) => form.setData('rubrik_bb', v)} />
            </div>
            <BidangTextarea
                id={`catatan-${i.id}`}
                label="Catatan pendampingan"
                baris={2}
                nilai={form.data.catatan_pendampingan}
                onUbah={(v) => form.setData('catatan_pendampingan', v)}
            />
            <BidangSakelar id={`aktif-${i.id}`} label="Tampilkan di website" nilai={form.data.aktif} onUbah={(v) => form.setData('aktif', v)} />
            <Button type="submit" size="sm" className="w-fit" disabled={form.processing}>
                <Save /> Simpan indikator {i.nomor_indikator}
            </Button>
        </form>
    );
}

function FormAspek({ a, ikon }: { a: Aspek; ikon: string[] }) {
    const form = useForm({
        nama: a.nama,
        deskripsi: a.deskripsi,
        contoh_pembiasaan: a.contoh_pembiasaan ?? '',
        fokus_pengamatan: a.fokus_pengamatan ?? '',
        ikon: a.ikon ?? '',
        warna: a.warna ?? 'pink',
        aktif: a.aktif,
    });

    return (
        <form
            className="grid gap-3 md:grid-cols-2"
            onSubmit={(e) => {
                e.preventDefault();
                form.put(`/admin/karakter/aspek/${a.id}`, { preserveScroll: true });
            }}
        >
            <BidangTeks id={`nama-${a.id}`} label="Nama aspek" wajib nilai={form.data.nama} onUbah={(v) => form.setData('nama', v)} galat={form.errors.nama} />
            <BidangTeks id={`fokus-${a.id}`} label="Fokus pengamatan" nilai={form.data.fokus_pengamatan} onUbah={(v) => form.setData('fokus_pengamatan', v)} />
            <BidangTextarea
                id={`desk-${a.id}`}
                label="Deskripsi"
                wajib
                className="md:col-span-2"
                nilai={form.data.deskripsi}
                onUbah={(v) => form.setData('deskripsi', v)}
                galat={form.errors.deskripsi}
            />
            <BidangTextarea
                id={`contohp-${a.id}`}
                label="Contoh pembiasaan"
                className="md:col-span-2"
                baris={2}
                nilai={form.data.contoh_pembiasaan}
                onUbah={(v) => form.setData('contoh_pembiasaan', v)}
            />
            <BidangPilih
                id={`ikon-${a.id}`}
                label="Ikon"
                nilai={form.data.ikon}
                onUbah={(v) => form.setData('ikon', v)}
                opsi={ikon.map((i) => ({ nilai: i, label: i }))}
                galat={form.errors.ikon}
            />
            <BidangPilih
                id={`warna-${a.id}`}
                label="Warna aksen"
                nilai={form.data.warna}
                onUbah={(v) => form.setData('warna', v)}
                opsi={['pink', 'rose', 'fuchsia', 'violet', 'amber', 'emerald', 'sky'].map((w) => ({ nilai: w, label: w }))}
            />
            <BidangSakelar id={`aktifa-${a.id}`} label="Tampilkan aspek" nilai={form.data.aktif} onUbah={(v) => form.setData('aktif', v)} />
            <Button type="submit" size="sm" className="w-fit self-end" disabled={form.processing}>
                <Save /> Simpan aspek
            </Button>
        </form>
    );
}

export default function KelolaKarakter({ aspek, ikon }: { aspek: Aspek[]; ikon: string[] }) {
    return (
        <HalamanAdmin
            judul="Aspek & Indikator Karakter"
            jejak={[{ judul: 'Aspek & Indikator' }]}
            deskripsi="Empat aspek dan 16 indikator sebagai materi referensi. Tidak ada penyimpanan skor atau data anak."
        >
            <div className="space-y-6">
                {aspek.map((a) => (
                    <KartuAdmin key={a.id} judul={`${a.kode_aspek} · ${a.nama}`}>
                        <FormAspek a={a} ikon={ikon} />
                        <Accordion type="multiple" className="mt-5 rounded-xl border px-4">
                            {a.indikator.map((i) => (
                                <AccordionItem key={i.id} value={String(i.id)}>
                                    <AccordionTrigger className="text-sm">
                                        {i.nomor_indikator}. {i.rumusan}
                                    </AccordionTrigger>
                                    <AccordionContent>
                                        <FormIndikator i={i} />
                                    </AccordionContent>
                                </AccordionItem>
                            ))}
                        </Accordion>
                    </KartuAdmin>
                ))}
            </div>
        </HalamanAdmin>
    );
}
