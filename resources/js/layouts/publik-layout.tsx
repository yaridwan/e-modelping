import type { ReactNode } from 'react';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { FooterPublik } from '@/components/publik/footer-publik';
import { HeaderPublik } from '@/components/publik/header-publik';
import { Seo } from '@/components/umum/seo';

export default function PublikLayout({ children }: { children: ReactNode }) {
    useFlashToast();
    return (
        <div className="flex min-h-screen flex-col">
            <Seo />
            <a href="#konten-utama" className="tautan-lewati">
                Lewati ke konten utama
            </a>
            <HeaderPublik />
            <main id="konten-utama" className="flex-1" tabIndex={-1}>
                {children}
            </main>
            <FooterPublik />
        </div>
    );
}
