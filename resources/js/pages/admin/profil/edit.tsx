import { router, useForm } from '@inertiajs/react';
import { Camera, Save, Trash2 } from 'lucide-react';
import { useRef } from 'react';
import { BidangPilih, BidangSakelar, BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { formatTanggal } from '@/lib/utils';

type Profil = {
    nama_lengkap: string;
    nama_panggilan: string | null;
    surel: string;
    nomor_telepon: string | null;
    jabatan: string | null;
    institusi: string | null;
    bidang_keahlian: string | null;
    biografi: string | null;
    situs_pribadi: string | null;
    surel_publik: string | null;
    nomor_kontak_publik: string | null;
    tampilkan_profil_publik: boolean;
    tampilkan_foto_publik: boolean;
    tampilkan_kontak_publik: boolean;
    tema_pilihan: string;
    inisial: string;
    foto: string | null;
    terakhir_masuk_pada: string | null;
};

export default function EditProfil({ profil }: { profil: Profil }) {
    const berkas = useRef<HTMLInputElement>(null);
    const form = useForm({
        nama_lengkap: profil.nama_lengkap,
        nama_panggilan: profil.nama_panggilan ?? '',
        nomor_telepon: profil.nomor_telepon ?? '',
        jabatan: profil.jabatan ?? '',
        institusi: profil.institusi ?? '',
        bidang_keahlian: profil.bidang_keahlian ?? '',
        biografi: profil.biografi ?? '',
        situs_pribadi: profil.situs_pribadi ?? '',
        surel_publik: profil.surel_publik ?? '',
        nomor_kontak_publik: profil.nomor_kontak_publik ?? '',
        tampilkan_profil_publik: profil.tampilkan_profil_publik,
        tampilkan_foto_publik: profil.tampilkan_foto_publik,
        tampilkan_kontak_publik: profil.tampilkan_kontak_publik,
        tema_pilihan: profil.tema_pilihan,
    });
    const unggah = (f: File) => router.post('/admin/profil/foto', { foto: f }, { forceFormData: true, preserveScroll: true });

    return (
        <HalamanAdmin
            judul="Profil Saya"
            jejak={[{ judul: 'Profil' }]}
            deskripsi={`Masuk terakhir: ${formatTanggal(profil.terakhir_masuk_pada, true)}. Surel akun & nomor telepon pribadi tidak pernah dipublikasikan.`}
        >
            <div className="grid gap-6 lg:grid-cols-[18rem_1fr]">
                <KartuAdmin judul="Foto profil">
                    <div className="flex flex-col items-center gap-4">
                        <Avatar className="size-32">
                            {profil.foto && <AvatarImage src={profil.foto} alt="Foto profil" />}
                            <AvatarFallback className="bg-secondary text-3xl font-bold text-secondary-foreground">{profil.inisial}</AvatarFallback>
                        </Avatar>
                        <input
                            ref={berkas}
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            className="sr-only"
                            id="foto"
                            onChange={(e) => e.target.files?.[0] && unggah(e.target.files[0])}
                        />
                        <div className="flex gap-2">
                            <Button size="sm" onClick={() => berkas.current?.click()}>
                                <Camera /> {profil.foto ? 'Ganti' : 'Unggah'}
                            </Button>
                            {profil.foto && (
                                <Button size="sm" variant="outline" onClick={() => router.delete('/admin/profil/foto', { preserveScroll: true })}>
                                    <Trash2 /> Hapus
                                </Button>
                            )}
                        </div>
                        <p className="text-center text-xs text-muted-foreground">JPG/PNG/WebP ≤ 4 MB. Tanpa foto, avatar inisial dipakai.</p>
                    </div>
                </KartuAdmin>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put('/admin/profil', { preserveScroll: true });
                    }}
                    className="space-y-6"
                >
                    <KartuAdmin judul="Data akun (privat)">
                        <div className="grid gap-4 md:grid-cols-2">
                            <BidangTeks
                                id="nama_lengkap"
                                label="Nama lengkap"
                                wajib
                                nilai={form.data.nama_lengkap}
                                onUbah={(v) => form.setData('nama_lengkap', v)}
                                galat={form.errors.nama_lengkap}
                            />
                            <BidangTeks
                                id="nama_panggilan"
                                label="Nama panggilan"
                                nilai={form.data.nama_panggilan}
                                onUbah={(v) => form.setData('nama_panggilan', v)}
                                galat={form.errors.nama_panggilan}
                            />
                            <BidangTeks id="surel_akun" label="Surel akun (ubah di Keamanan)" nilai={profil.surel} onUbah={() => undefined} />
                            <BidangTeks
                                id="telepon"
                                label="Nomor telepon (privat, opsional)"
                                nilai={form.data.nomor_telepon}
                                onUbah={(v) => form.setData('nomor_telepon', v)}
                                galat={form.errors.nomor_telepon}
                            />
                            <BidangPilih
                                id="tema"
                                label="Tema admin"
                                nilai={form.data.tema_pilihan}
                                onUbah={(v) => form.setData('tema_pilihan', v)}
                                opsi={[
                                    { nilai: 'sistem', label: 'Ikuti sistem' },
                                    { nilai: 'terang', label: 'Terang' },
                                    { nilai: 'gelap', label: 'Gelap' },
                                ]}
                            />
                        </div>
                    </KartuAdmin>
                    <KartuAdmin judul="Profil penyusun (dapat dipublikasikan)">
                        <div className="grid gap-4 md:grid-cols-2">
                            <BidangTeks id="jabatan" label="Jabatan" nilai={form.data.jabatan} onUbah={(v) => form.setData('jabatan', v)} />
                            <BidangTeks id="institusi" label="Institusi (opsional)" nilai={form.data.institusi} onUbah={(v) => form.setData('institusi', v)} />
                            <BidangTeks
                                id="bidang"
                                label="Bidang keahlian"
                                className="md:col-span-2"
                                nilai={form.data.bidang_keahlian}
                                onUbah={(v) => form.setData('bidang_keahlian', v)}
                            />
                            <BidangTextarea
                                id="biografi"
                                label="Biografi"
                                className="md:col-span-2"
                                baris={5}
                                maks={3000}
                                nilai={form.data.biografi}
                                onUbah={(v) => form.setData('biografi', v)}
                                galat={form.errors.biografi}
                            />
                            <BidangTeks
                                id="situs"
                                label="Situs pribadi"
                                nilai={form.data.situs_pribadi}
                                onUbah={(v) => form.setData('situs_pribadi', v)}
                                galat={form.errors.situs_pribadi}
                            />
                            <BidangTeks
                                id="surel_publik"
                                label="Surel kontak publik (terpisah dari surel akun)"
                                tipe="email"
                                nilai={form.data.surel_publik}
                                onUbah={(v) => form.setData('surel_publik', v)}
                                galat={form.errors.surel_publik}
                            />
                            <BidangTeks
                                id="kontak_publik"
                                label="Nomor kontak publik"
                                nilai={form.data.nomor_kontak_publik}
                                onUbah={(v) => form.setData('nomor_kontak_publik', v)}
                                galat={form.errors.nomor_kontak_publik}
                            />
                        </div>
                        <div className="mt-4 grid gap-3 md:grid-cols-3">
                            <BidangSakelar
                                id="pub"
                                label="Tampilkan profil publik"
                                nilai={form.data.tampilkan_profil_publik}
                                onUbah={(v) => form.setData('tampilkan_profil_publik', v)}
                                petunjuk="Nama, jabatan, institusi, bidang, biografi, situs."
                            />
                            <BidangSakelar
                                id="pub_foto"
                                label="Tampilkan foto"
                                nilai={form.data.tampilkan_foto_publik}
                                onUbah={(v) => form.setData('tampilkan_foto_publik', v)}
                            />
                            <BidangSakelar
                                id="pub_kontak"
                                label="Tampilkan kontak publik"
                                nilai={form.data.tampilkan_kontak_publik}
                                onUbah={(v) => form.setData('tampilkan_kontak_publik', v)}
                            />
                        </div>
                    </KartuAdmin>
                    <Button type="submit" disabled={form.processing}>
                        <Save /> Simpan profil
                    </Button>
                </form>
            </div>
        </HalamanAdmin>
    );
}
