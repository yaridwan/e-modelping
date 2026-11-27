import { Link, router, useForm } from '@inertiajs/react';
import { LogIn, RefreshCw, ShieldQuestion } from 'lucide-react';
import { useEffect } from 'react';
import { Bidang, BidangTeks, PesanGalat } from '@/components/admin/bidang';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

type Captcha = { id: string; soal: string; kedaluwarsa_detik: number };

export default function Masuk({ captcha, status }: { captcha: Captcha; status: string | null }) {
    const form = useForm({ surel: '', kata_sandi: '', captcha_id: captcha.id, captcha_jawaban: '', ingat_saya: false });

    // Setiap tantangan baru (setelah gagal atau "Ganti soal") mengganti ID dan mengosongkan jawaban.
    useEffect(() => {
        form.setData((d) => ({ ...d, captcha_id: captcha.id, captcha_jawaban: '' }));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [captcha.id]);

    const gantiSoal = () => router.reload({ only: ['captcha'] });

    return (
        <>
            <h1 className="text-2xl font-extrabold">Masuk Admin</h1>
            <p className="mt-1 text-sm text-muted-foreground">Halaman khusus pengelola website. Pembaca tidak perlu login.</p>
            {status && (
                <p className="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
                    {status}
                </p>
            )}
            <form
                className="mt-6 grid gap-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post('/admin/masuk', {
                        onError: () => router.reload({ only: ['captcha'] }),
                        onFinish: () => form.reset('kata_sandi', 'captcha_jawaban'),
                    });
                }}
            >
                <BidangTeks
                    id="surel"
                    label="Surel"
                    tipe="email"
                    wajib
                    autoComplete="username"
                    nilai={form.data.surel}
                    onUbah={(v) => form.setData('surel', v)}
                    galat={form.errors.surel}
                />
                <Bidang id="kata_sandi" label="Kata sandi" wajib galat={form.errors.kata_sandi}>
                    <PasswordInput
                        id="kata_sandi"
                        required
                        autoComplete="current-password"
                        value={form.data.kata_sandi}
                        onChange={(e) => form.setData('kata_sandi', e.target.value)}
                        aria-invalid={!!form.errors.kata_sandi}
                    />
                </Bidang>
                <fieldset className="rounded-xl border bg-muted/40 p-4">
                    <legend className="flex items-center gap-1.5 px-1 text-sm font-semibold">
                        <ShieldQuestion className="size-4 text-primary" aria-hidden /> Soal keamanan
                    </legend>
                    <div className="flex flex-wrap items-end gap-3">
                        <div className="grid gap-1.5">
                            <label htmlFor="captcha_jawaban" className="text-sm font-semibold" aria-live="polite">
                                {`Berapa hasil ${captcha.soal}?`}
                            </label>
                            <Input
                                id="captcha_jawaban"
                                inputMode="numeric"
                                pattern="[0-9]*"
                                maxLength={3}
                                required
                                autoComplete="off"
                                className="w-28"
                                value={form.data.captcha_jawaban}
                                onChange={(e) => form.setData('captcha_jawaban', e.target.value.replace(/[^0-9]/g, ''))}
                                aria-invalid={!!form.errors.captcha_jawaban}
                                aria-describedby="captcha-bantuan"
                            />
                        </div>
                        <Button type="button" variant="outline" size="sm" onClick={gantiSoal}>
                            <RefreshCw aria-hidden /> Ganti soal
                        </Button>
                    </div>
                    <p id="captcha-bantuan" className="mt-2 text-xs text-muted-foreground">
                        Jawab dengan angka. Soal berlaku {Math.round(captcha.kedaluwarsa_detik / 60)} menit dan hanya sekali pakai.
                    </p>
                    <PesanGalat pesan={form.errors.captcha_jawaban} />
                </fieldset>
                <div className="flex items-center justify-between gap-3">
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox checked={form.data.ingat_saya} onCheckedChange={(v) => form.setData('ingat_saya', v === true)} />
                        Ingat saya
                    </label>
                    <Link href="/admin/lupa-sandi" className="text-sm font-semibold text-primary hover:underline">
                        Lupa sandi?
                    </Link>
                </div>
                <Button type="submit" disabled={form.processing} className="w-full">
                    <LogIn aria-hidden /> {form.processing ? 'Memeriksa…' : 'Masuk'}
                </Button>
            </form>
        </>
    );
}
