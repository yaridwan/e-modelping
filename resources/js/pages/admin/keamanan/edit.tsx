import { useForm } from '@inertiajs/react';
import { KeyRound, LogOut, Mail } from 'lucide-react';
import { Bidang, BidangSakelar, BidangTeks } from '@/components/admin/bidang';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { formatTanggal } from '@/lib/utils';

export default function Keamanan({
    akun,
}: {
    akun: { surel: string; sandi_diubah_pada: string | null; terakhir_masuk_pada: string | null; wajib_ganti_sandi: boolean };
}) {
    const surel = useForm({ surel: akun.surel, sandi_saat_ini: '' });
    const sandi = useForm({ sandi_saat_ini: '', kata_sandi: '', kata_sandi_confirmation: '', keluarkan_sesi_lain: true });
    const sesi = useForm({ sandi_saat_ini: '' });

    return (
        <HalamanAdmin judul="Keamanan" jejak={[{ judul: 'Keamanan' }]} deskripsi={`Sandi terakhir diubah: ${formatTanggal(akun.sandi_diubah_pada, true)}.`}>
            <div className="grid gap-6 xl:grid-cols-2">
                <KartuAdmin
                    judul={
                        <span className="flex items-center gap-2">
                            <KeyRound className="size-4" /> Ganti sandi
                        </span>
                    }
                    deskripsi={akun.wajib_ganti_sandi ? 'Anda memakai sandi sementara. Segera ganti.' : undefined}
                >
                    <form
                        className="grid gap-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            sandi.put('/admin/keamanan/sandi', { preserveScroll: true, onSuccess: () => sandi.reset() });
                        }}
                    >
                        <Bidang id="sandi_lama" label="Sandi saat ini" wajib galat={sandi.errors.sandi_saat_ini}>
                            <PasswordInput
                                id="sandi_lama"
                                autoComplete="current-password"
                                value={sandi.data.sandi_saat_ini}
                                onChange={(e) => sandi.setData('sandi_saat_ini', e.target.value)}
                            />
                        </Bidang>
                        <Bidang
                            id="sandi_baru"
                            label="Sandi baru"
                            wajib
                            galat={sandi.errors.kata_sandi}
                            petunjuk="Lokal: min. 8 karakter huruf & angka. Produksi: min. 12 karakter dengan huruf besar/kecil, angka, simbol."
                        >
                            <PasswordInput
                                id="sandi_baru"
                                autoComplete="new-password"
                                value={sandi.data.kata_sandi}
                                onChange={(e) => sandi.setData('kata_sandi', e.target.value)}
                            />
                        </Bidang>
                        <Bidang id="sandi_ulang" label="Ulangi sandi baru" wajib>
                            <PasswordInput
                                id="sandi_ulang"
                                autoComplete="new-password"
                                value={sandi.data.kata_sandi_confirmation}
                                onChange={(e) => sandi.setData('kata_sandi_confirmation', e.target.value)}
                            />
                        </Bidang>
                        <BidangSakelar
                            id="cabut"
                            label="Keluarkan sesi di perangkat lain"
                            nilai={sandi.data.keluarkan_sesi_lain}
                            onUbah={(v) => sandi.setData('keluarkan_sesi_lain', v)}
                        />
                        <Button type="submit" className="w-fit" disabled={sandi.processing}>
                            Simpan sandi baru
                        </Button>
                    </form>
                </KartuAdmin>
                <div className="space-y-6">
                    <KartuAdmin
                        judul={
                            <span className="flex items-center gap-2">
                                <Mail className="size-4" /> Surel login
                            </span>
                        }
                        deskripsi="Memerlukan verifikasi sandi saat ini."
                    >
                        <form
                            className="grid gap-4"
                            onSubmit={(e) => {
                                e.preventDefault();
                                surel.put('/admin/keamanan/surel', { preserveScroll: true, onSuccess: () => surel.setData('sandi_saat_ini', '') });
                            }}
                        >
                            <BidangTeks
                                id="surel"
                                label="Surel login"
                                tipe="email"
                                wajib
                                nilai={surel.data.surel}
                                onUbah={(v) => surel.setData('surel', v)}
                                galat={surel.errors.surel}
                                autoComplete="username"
                            />
                            <Bidang id="sandi_surel" label="Sandi saat ini" wajib galat={surel.errors.sandi_saat_ini}>
                                <PasswordInput
                                    id="sandi_surel"
                                    autoComplete="current-password"
                                    value={surel.data.sandi_saat_ini}
                                    onChange={(e) => surel.setData('sandi_saat_ini', e.target.value)}
                                />
                            </Bidang>
                            <Button type="submit" className="w-fit" disabled={surel.processing}>
                                Simpan surel
                            </Button>
                        </form>
                    </KartuAdmin>
                    <KartuAdmin
                        judul={
                            <span className="flex items-center gap-2">
                                <LogOut className="size-4" /> Sesi perangkat lain
                            </span>
                        }
                        deskripsi={`Masuk terakhir: ${formatTanggal(akun.terakhir_masuk_pada, true)}.`}
                    >
                        <form
                            className="grid gap-4"
                            onSubmit={(e) => {
                                e.preventDefault();
                                sesi.post('/admin/keamanan/keluar-perangkat-lain', { preserveScroll: true, onSuccess: () => sesi.reset() });
                            }}
                        >
                            <Bidang id="sandi_sesi" label="Sandi saat ini" wajib galat={sesi.errors.sandi_saat_ini}>
                                <PasswordInput
                                    id="sandi_sesi"
                                    autoComplete="current-password"
                                    value={sesi.data.sandi_saat_ini}
                                    onChange={(e) => sesi.setData('sandi_saat_ini', e.target.value)}
                                />
                            </Bidang>
                            <Button type="submit" variant="outline" className="w-fit" disabled={sesi.processing}>
                                Keluarkan perangkat lain
                            </Button>
                        </form>
                        <p className="mt-4 text-xs text-muted-foreground">
                            Pemulihan akun: gunakan “Lupa sandi” di halaman masuk (tautan sekali pakai 60 menit ke surel akun).
                        </p>
                    </KartuAdmin>
                </div>
            </div>
        </HalamanAdmin>
    );
}
