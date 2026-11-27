import { Link, useForm } from '@inertiajs/react';
import { Bidang, BidangTeks } from '@/components/admin/bidang';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';

export default function AturUlangSandi({ token, valid }: { token: string; valid: boolean }) {
    const form = useForm({ token, surel: '', kata_sandi: '', kata_sandi_confirmation: '' });

    if (!valid) {
        return (
            <>
                <h1 className="text-2xl font-extrabold">Tautan tidak berlaku</h1>
                <p className="mt-2 text-sm text-muted-foreground">Tautan atur ulang sandi tidak valid, sudah dipakai, atau kedaluwarsa.</p>
                <Button asChild className="mt-6">
                    <Link href="/admin/lupa-sandi">Minta tautan baru</Link>
                </Button>
            </>
        );
    }

    return (
        <>
            <h1 className="text-2xl font-extrabold">Atur ulang sandi</h1>
            <form
                className="mt-6 grid gap-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post('/admin/atur-ulang-sandi', { onFinish: () => form.reset('kata_sandi', 'kata_sandi_confirmation') });
                }}
            >
                <BidangTeks
                    id="surel"
                    label="Surel akun"
                    tipe="email"
                    wajib
                    autoComplete="username"
                    nilai={form.data.surel}
                    onUbah={(v) => form.setData('surel', v)}
                    galat={form.errors.surel}
                />
                <Bidang
                    id="kata_sandi"
                    label="Sandi baru"
                    wajib
                    galat={form.errors.kata_sandi}
                    petunjuk="Minimal 8 karakter berisi huruf dan angka (produksi: 12 karakter dengan huruf besar/kecil, angka, simbol)."
                >
                    <PasswordInput
                        id="kata_sandi"
                        required
                        autoComplete="new-password"
                        value={form.data.kata_sandi}
                        onChange={(e) => form.setData('kata_sandi', e.target.value)}
                    />
                </Bidang>
                <Bidang id="kata_sandi_confirmation" label="Ulangi sandi baru" wajib>
                    <PasswordInput
                        id="kata_sandi_confirmation"
                        required
                        autoComplete="new-password"
                        value={form.data.kata_sandi_confirmation}
                        onChange={(e) => form.setData('kata_sandi_confirmation', e.target.value)}
                    />
                </Bidang>
                <Button type="submit" disabled={form.processing}>
                    Simpan sandi baru
                </Button>
            </form>
        </>
    );
}
