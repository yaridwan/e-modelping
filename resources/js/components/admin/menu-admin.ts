import {
    BarChart3,
    BookOpen,
    CircleHelp,
    FileText,
    Files,
    History,
    Home,
    Images,
    Inbox,
    LayoutDashboard,
    Layers,
    Library,
    Link2,
    Menu as IkonMenu,
    Settings,
    Shield,
    Sparkles,
    UserRound,
    Waypoints,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

export type ItemMenuAdmin = { judul: string; href: string; ikon: LucideIcon; lencana?: 'pesan_baru' };

export const GRUP_MENU_ADMIN: { label: string; item: ItemMenuAdmin[] }[] = [
    { label: 'Ringkasan', item: [{ judul: 'Dashboard', href: '/admin', ikon: LayoutDashboard }] },
    {
        label: 'Materi',
        item: [
            { judul: 'Bab & Subbab', href: '/admin/bab', ikon: BookOpen },
            { judul: 'Dokumen PDF', href: '/admin/dokumen', ikon: Files },
            { judul: 'Fase PING', href: '/admin/fase', ikon: Layers },
            { judul: 'Aspek & Indikator', href: '/admin/karakter', ikon: Sparkles },
            { judul: 'Referensi Pustaka', href: '/admin/referensi', ikon: Library },
        ],
    },
    {
        label: 'Website',
        item: [
            { judul: 'Beranda Dinamis', href: '/admin/beranda', ikon: Home },
            { judul: 'Halaman Informasi', href: '/admin/halaman', ikon: FileText },
            { judul: 'Pustaka Media', href: '/admin/media', ikon: Images },
            { judul: 'Galeri', href: '/admin/galeri', ikon: Images },
            { judul: 'FAQ', href: '/admin/faq', ikon: CircleHelp },
            { judul: 'Menu & Tautan Sosial', href: '/admin/menu', ikon: IkonMenu },
            { judul: 'Pengalihan URL', href: '/admin/pengalihan', ikon: Waypoints },
        ],
    },
    {
        label: 'Interaksi',
        item: [
            { judul: 'Pesan Masuk', href: '/admin/pesan', ikon: Inbox, lencana: 'pesan_baru' },
            { judul: 'Statistik', href: '/admin/statistik', ikon: BarChart3 },
            { judul: 'Log Aktivitas', href: '/admin/log-aktivitas', ikon: History },
        ],
    },
    {
        label: 'Akun & Sistem',
        item: [
            { judul: 'Pengaturan Website', href: '/admin/pengaturan', ikon: Settings },
            { judul: 'Profil Saya', href: '/admin/profil', ikon: UserRound },
            { judul: 'Keamanan', href: '/admin/keamanan', ikon: Shield },
        ],
    },
];

export const IKON_TAUTAN = Link2;
