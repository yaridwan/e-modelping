import { Link, router, usePage } from '@inertiajs/react';
import { ExternalLink, LogOut, Search, Shield, UserRound } from 'lucide-react';
import type { ReactNode } from 'react';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { useMemo, useState } from 'react';
import { GRUP_MENU_ADMIN } from '@/components/admin/menu-admin';
import { Logo } from '@/components/umum/logo';
import { PengalihTema } from '@/components/umum/pengalih-tema';
import { Seo } from '@/components/umum/seo';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarRail,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import type { Appearance } from '@/hooks/use-appearance';
import type { PropsBersama } from '@/types';

const TEMA_KE_PROFIL: Record<Appearance, string> = { light: 'terang', dark: 'gelap', system: 'sistem' };

function SidebarAdmin() {
    const { url, props } = usePage<PropsBersama>();
    const lencana = props.lencana_admin ?? null;
    const [cari, setCari] = useState('');
    const grup = useMemo(() => {
        const q = cari.trim().toLowerCase();

        return GRUP_MENU_ADMIN.map((g) => ({ ...g, item: g.item.filter((i) => !q || i.judul.toLowerCase().includes(q)) })).filter((g) => g.item.length);
    }, [cari]);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin">
                                <Logo tinggi={34} />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <div className="relative px-1 group-data-[collapsible=icon]:hidden">
                    <label htmlFor="cari-menu-admin" className="sr-only">
                        Cari menu
                    </label>
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted-foreground" aria-hidden />
                    <input
                        id="cari-menu-admin"
                        value={cari}
                        onChange={(e) => setCari(e.target.value)}
                        placeholder="Cari menu…"
                        className="h-8 w-full rounded-md border border-sidebar-border bg-background pr-2 pl-8 text-sm outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                    />
                </div>
            </SidebarHeader>
            <SidebarContent>
                {grup.map((g) => (
                    <SidebarGroup key={g.label} className="py-1">
                        <SidebarGroupLabel>{g.label}</SidebarGroupLabel>
                        <SidebarMenu>
                            {g.item.map((i) => {
                                const aktif = i.href === '/admin' ? url === '/admin' || url.startsWith('/admin?') : url.startsWith(i.href);
                                const angka = i.lencana && lencana ? lencana[i.lencana] : 0;
                                const judul = angka ? `${i.judul} (${angka} baru)` : i.judul;

                                return (
                                    <SidebarMenuItem key={i.href}>
                                        <SidebarMenuButton asChild isActive={aktif} tooltip={{ children: judul }}>
                                            <Link href={i.href} prefetch aria-current={aktif ? 'page' : undefined}>
                                                <i.ikon aria-hidden />
                                                <span>{i.judul}</span>
                                                {angka > 0 && <span className="sr-only">, {angka} baru</span>}
                                            </Link>
                                        </SidebarMenuButton>
                                        {angka > 0 && (
                                            <SidebarMenuBadge
                                                className="rounded-full bg-primary px-1.5 text-primary-foreground peer-hover/menu-button:text-primary-foreground peer-data-[active=true]/menu-button:text-primary-foreground"
                                                aria-hidden
                                            >
                                                {angka > 99 ? '99+' : angka}
                                            </SidebarMenuBadge>
                                        )}
                                    </SidebarMenuItem>
                                );
                            })}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
                {!grup.length && <p className="px-4 text-sm text-muted-foreground">Menu tidak ditemukan.</p>}
            </SidebarContent>
            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton asChild tooltip={{ children: 'Lihat website' }}>
                            <a href="/" target="_blank" rel="noopener">
                                <ExternalLink aria-hidden />
                                <span>Lihat Website</span>
                            </a>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}

export default function AdminLayout({ children }: { children: ReactNode }) {
    useFlashToast();
    const { auth, sidebarOpen } = usePage<PropsBersama>().props;
    const p = auth.pengguna;

    return (
        <SidebarProvider defaultOpen={sidebarOpen}>
            <Seo judulAdmin="Admin" />
            <SidebarAdmin />
            <SidebarInset className="min-w-0">
                <header className="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-2 border-b bg-background/90 px-3 backdrop-blur sm:px-4">
                    <SidebarTrigger aria-label="Perkecil/perbesar sidebar" />
                    <div className="ml-auto flex items-center gap-1">
                        <Button asChild variant="ghost" size="sm" className="hidden sm:inline-flex">
                            <a href="/" target="_blank" rel="noopener">
                                <ExternalLink aria-hidden /> Lihat Website
                            </a>
                        </Button>
                        <PengalihTema
                            onUbah={(a) => router.put('/admin/profil/tema', { tema_pilihan: TEMA_KE_PROFIL[a] }, { preserveScroll: true, preserveState: true })}
                        />
                        {p && (
                            <DropdownMenu>
                                <DropdownMenuTrigger
                                    className="rounded-full focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                    aria-label="Menu akun"
                                >
                                    <Avatar className="size-8">
                                        {p.foto && <AvatarImage src={p.foto} alt="" />}
                                        <AvatarFallback className="bg-secondary text-xs font-bold text-secondary-foreground">{p.inisial}</AvatarFallback>
                                    </Avatar>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-56">
                                    <DropdownMenuLabel className="font-normal">
                                        <span className="block truncate font-semibold">{p.nama_lengkap}</span>
                                        <span className="block truncate text-xs text-muted-foreground">{p.surel}</span>
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem asChild>
                                        <Link href="/admin/profil">
                                            <UserRound className="mr-2 size-4" aria-hidden /> Profil saya
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild>
                                        <Link href="/admin/keamanan">
                                            <Shield className="mr-2 size-4" aria-hidden /> Keamanan
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem asChild>
                                        <Link href="/admin/keluar" method="post" as="button" className="w-full">
                                            <LogOut className="mr-2 size-4" aria-hidden /> Keluar
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}
                    </div>
                </header>
                {p?.wajib_ganti_sandi && (
                    <div className="border-b bg-amber-50 px-4 py-2 text-sm text-amber-900 dark:bg-amber-950/40 dark:text-amber-200" role="status">
                        Anda masih memakai sandi sementara.{' '}
                        <Link href="/admin/keamanan" className="font-semibold underline">
                            Ganti sandi sekarang
                        </Link>
                        .
                    </div>
                )}
                <div className="mx-auto w-full max-w-7xl flex-1 p-4 sm:p-6">{children}</div>
            </SidebarInset>
        </SidebarProvider>
    );
}
