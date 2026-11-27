import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AdminLayout from '@/layouts/admin-layout';
import AuthLayout from '@/layouts/auth-layout';
import PublikLayout from '@/layouts/publik-layout';

void createInertiaApp({
    // Judul lengkap sudah dibentuk server (templat SEO dari pengaturan).
    title: (title) => title,
    layout: (name) => {
        switch (true) {
            case name.startsWith('publik/') || name === 'galat':
                return PublikLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('admin/'):
                return AdminLayout;
            default:
                return PublikLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={150}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#EC4899',
    },
});

initializeTheme();
