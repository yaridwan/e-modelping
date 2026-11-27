import { Link, useForm } from '@inertiajs/react';
import { BidangTeks } from '@/components/admin/bidang';
import { Button } from '@/components/ui/button';

export default function LupaSandi({ status }: { status: string | null }) {
    const form = useForm({ surel: '' });

    return (
        <>
            <h1 className="text-2xl font-extrabold">Lupa sandi</h1>
            <p className="mt-1 text-sm text-muted-foreground">Masukkan surel akun admin. Jika terdaftar, tautan atur ulang (berlaku 60 menit) akan dikirim.</p>
            {status && (
                <p className="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
                    {status}
                </p>
            )}
            <form
                className="mt-6 grid gap-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post('/admin/lupa-sandi', { onSuccess: () => form.reset() });
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
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? 'Mengirim…' : 'Kirim tautan atur ulang'}
                </Button>
                <Link href="/admin/masuk" className="text-center text-sm text-primary hover:underline">
                    Kembali ke halaman masuk
                </Link>
            </form>
        </>
    );
}
