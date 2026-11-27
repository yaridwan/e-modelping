import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useState } from 'react';
import { BidangPilih, BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { LencanaStatus } from '@/components/admin/daftar';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { TombolKonfirmasi } from '@/components/admin/konfirmasi';
import { BidangMedia } from '@/components/admin/pemilih-media';
import { Button } from '@/components/ui/button';

type G = {
    id: number;
    judul: string;
    slug: string;
    ringkasan: string | null;
    media_id: number;
    bab_id: number | null;
    fase_id: number | null;
    lokasi_umum: string | null;
    keterangan: string | null;
    teks_alternatif: string;
    dasar_izin_publikasi: string | null;
    urutan: number;
    tanggal_kegiatan: string | null;
    status: string;
    media: { id: number; url: string | null; judul: string } | null;
};

export default function FormGaleri({ galeri, bab, fase }: { galeri: G | null; bab: { id: number; label: string }[]; fase: { id: number; label: string }[] }) {
    const [media, setMedia] = useState(galeri?.media ?? null);
    const form = useForm({
        judul: galeri?.judul ?? '',
        slug: galeri?.slug ?? '',
        ringkasan: galeri?.ringkasan ?? '',
        media_id: galeri?.media_id ?? null,
        bab_id: galeri?.bab_id ? String(galeri.bab_id) : '',
        fase_id: galeri?.fase_id ? String(galeri.fase_id) : '',
        tanggal_kegiatan: galeri?.tanggal_kegiatan ?? '',
        lokasi_umum: galeri?.lokasi_umum ?? '',
        keterangan: galeri?.keterangan ?? '',
        teks_alternatif: galeri?.teks_alternatif ?? '',
        dasar_izin_publikasi: galeri?.dasar_izin_publikasi ?? '',
        urutan: galeri?.urutan ?? 0,
    });

    return (
        <HalamanAdmin
            judul={galeri ? galeri.judul : 'Item galeri baru'}
            jejak={[{ judul: 'Galeri', href: '/admin/galeri' }, { judul: galeri ? galeri.judul : 'Baru' }]}
            deskripsi={galeri && <LencanaStatus status={galeri.status} />}
            aksi={
                galeri && (
                    <>
                        {galeri.status !== 'terbit' ? (
                            <TombolKonfirmasi
                                url={`/admin/galeri/${galeri.id}/status`}
                                metode="post"
                                data={{ status: 'terbit' }}
                                varian="default"
                                labelAksi="Terbitkan"
                                judul="Terbitkan item galeri?"
                                deskripsi="Pastikan izin publikasi dimiliki, terutama bila memuat anak. Jangan mencantumkan nama atau data pribadi anak."
                            >
                                Terbitkan
                            </TombolKonfirmasi>
                        ) : (
                            <TombolKonfirmasi
                                url={`/admin/galeri/${galeri.id}/status`}
                                metode="post"
                                data={{ status: 'draf' }}
                                varian="outline"
                                labelAksi="Jadikan draf"
                                judul="Sembunyikan dari publik?"
                                deskripsi="Item akan kembali menjadi draf."
                            >
                                Jadikan draf
                            </TombolKonfirmasi>
                        )}
                        <TombolKonfirmasi
                            url={`/admin/galeri/${galeri.id}`}
                            judul="Hapus item galeri?"
                            deskripsi="Item dihapus; berkas gambar tetap di pustaka media."
                        >
                            Hapus
                        </TombolKonfirmasi>
                    </>
                )
            }
        >
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    if (galeri) {
                        form.put(`/admin/galeri/${galeri.id}`, { preserveScroll: true });
                    } else {
                        form.post('/admin/galeri');
                    }
                }}
            >
                <KartuAdmin>
                    <div className="grid gap-4 md:grid-cols-2">
                        <BidangTeks id="judul" label="Judul" wajib nilai={form.data.judul} onUbah={(v) => form.setData('judul', v)} galat={form.errors.judul} />
                        <BidangTeks id="slug" label="Slug" wajib nilai={form.data.slug} onUbah={(v) => form.setData('slug', v)} galat={form.errors.slug} />
                        <BidangMedia
                            id="media"
                            label="Gambar"
                            media={media}
                            onUbah={(m) => {
                                setMedia(m);
                                form.setData('media_id', m?.id ?? null);
                            }}
                            galat={form.errors.media_id}
                        />
                        <BidangTeks
                            id="alt"
                            label="Teks alternatif"
                            wajib
                            nilai={form.data.teks_alternatif}
                            onUbah={(v) => form.setData('teks_alternatif', v)}
                            galat={form.errors.teks_alternatif}
                        />
                        <BidangPilih
                            id="bab_id"
                            label="Terkait bab"
                            kosong="—"
                            nilai={form.data.bab_id}
                            onUbah={(v) => form.setData('bab_id', v)}
                            opsi={bab.map((b) => ({ nilai: b.id, label: b.label }))}
                        />
                        <BidangPilih
                            id="fase_id"
                            label="Terkait fase"
                            kosong="—"
                            nilai={form.data.fase_id}
                            onUbah={(v) => form.setData('fase_id', v)}
                            opsi={fase.map((f) => ({ nilai: f.id, label: f.label }))}
                        />
                        <BidangTeks
                            id="tanggal"
                            label="Tanggal kegiatan"
                            tipe="date"
                            nilai={form.data.tanggal_kegiatan}
                            onUbah={(v) => form.setData('tanggal_kegiatan', v)}
                            galat={form.errors.tanggal_kegiatan}
                        />
                        <BidangTeks
                            id="lokasi"
                            label="Lokasi umum"
                            placeholder="mis. Kelas B, satuan PAUD"
                            nilai={form.data.lokasi_umum}
                            onUbah={(v) => form.setData('lokasi_umum', v)}
                        />
                        <BidangTextarea
                            id="ringkasan"
                            label="Ringkasan"
                            className="md:col-span-2"
                            baris={2}
                            nilai={form.data.ringkasan}
                            onUbah={(v) => form.setData('ringkasan', v)}
                        />
                        <BidangTextarea
                            id="keterangan"
                            label="Keterangan"
                            className="md:col-span-2"
                            baris={2}
                            nilai={form.data.keterangan}
                            onUbah={(v) => form.setData('keterangan', v)}
                        />
                        <BidangTextarea
                            id="izin"
                            label="Dasar izin publikasi"
                            className="md:col-span-2"
                            baris={2}
                            nilai={form.data.dasar_izin_publikasi}
                            onUbah={(v) => form.setData('dasar_izin_publikasi', v)}
                            galat={form.errors.dasar_izin_publikasi}
                            petunjuk="Wajib sebelum terbit. Mis. persetujuan tertulis orang tua/lembaga. Jangan menuliskan nama anak."
                        />
                    </div>
                    <Button type="submit" className="mt-5" disabled={form.processing}>
                        <Save /> Simpan
                    </Button>
                </KartuAdmin>
            </form>
        </HalamanAdmin>
    );
}
