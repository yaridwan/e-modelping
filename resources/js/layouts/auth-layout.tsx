import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { Logo } from '@/components/umum/logo';
import { PengalihTema } from '@/components/umum/pengalih-tema';
import { Seo } from '@/components/umum/seo';

export default function AuthLayout({ children }: { children: ReactNode }) {
    useFlashToast();
    return (
        <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-background px-4 py-10">
            <Seo />
            <div className="pointer-events-none absolute inset-0 latar-titik opacity-60" aria-hidden />
            <div className="absolute top-4 right-4">
                <PengalihTema />
            </div>
            <main className="relative w-full max-w-md">
                <Link href="/" className="mx-auto mb-6 flex w-fit rounded-lg">
                    <Logo tinggi={56} />
                </Link>
                <div className="rounded-2xl border bg-card p-6 shadow-lembut sm:p-8">{children}</div>
                <p className="mt-6 text-center text-sm text-muted-foreground">
                    <Link href="/" className="hover:text-primary">
                        ← Kembali ke website
                    </Link>
                </p>
            </main>
        </div>
    );
}
