import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, LayoutDashboard, LogIn, Menu, Search } from 'lucide-react';
import { useState } from 'react';
import { Logo } from '@/components/umum/logo';
import { PengalihTema } from '@/components/umum/pengalih-tema';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import type { ItemNavigasi, PropsBersama } from '@/types';

function TautanNav({ item, className, onClick }: { item: ItemNavigasi; className?: string; onClick?: () => void }) {
    const { url } = usePage();
    const aktif = item.href === '/' ? url === '/' : url.startsWith(item.href.split('#')[0]);
    if (item.eksternal) {
        return (
            <a href={item.href} target={item.tab_baru ? '_blank' : undefined} rel="noopener noreferrer" className={className} onClick={onClick}>
                {item.label}
            </a>
        );
    }

    return (
        <Link href={item.href} className={cn(className, aktif && 'text-primary')} aria-current={aktif ? 'page' : undefined} onClick={onClick} prefetch>
            {item.label}
        </Link>
    );
}

export function FormCari({ className, idInput = 'cari-header' }: { className?: string; idInput?: string }) {
    return (
        <form action="/cari" method="get" role="search" className={cn('relative', className)}>
            <label htmlFor={idInput} className="sr-only">
                Cari materi
            </label>
            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden />
            <input
                id={idInput}
                name="q"
                type="search"
                required
                minLength={2}
                maxLength={80}
                placeholder="Cari materi…"
                className="h-10 w-full rounded-full border border-input bg-card pr-3 pl-9 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            />
        </form>
    );
}

/**
 * Tombol akses admin: "Masuk Admin" untuk tamu, "Dashboard" bila admin sudah masuk.
 * Pembaca tidak perlu login; tombol ini hanya pintu masuk pengelola website.
 */
function TombolAdmin({ masuk, varian = 'header', onClick }: { masuk: boolean; varian?: 'header' | 'drawer'; onClick?: () => void }) {
    const href = masuk ? '/admin' : '/admin/masuk';
    const label = masuk ? 'Dashboard' : 'Masuk Admin';
    const Ikon = masuk ? LayoutDashboard : LogIn;

    if (varian === 'drawer') {
        return (
            <Button asChild variant={masuk ? 'outline' : 'default'} className="w-full justify-center rounded-lg">
                <a href={href} rel="nofollow" onClick={onClick}>
                    <Ikon aria-hidden /> {label}
                </a>
            </Button>
        );
    }

    return (
        <>
            <Button asChild variant="outline" size="sm" className="hidden rounded-full sm:inline-flex">
                <a href={href} rel="nofollow">
                    <Ikon aria-hidden /> {label}
                </a>
            </Button>
            <Button asChild variant="ghost" size="icon" className="sm:hidden">
                <a href={href} rel="nofollow" aria-label={label} title={label}>
                    <Ikon className="size-5" aria-hidden />
                </a>
            </Button>
        </>
    );
}

export function HeaderPublik() {
    const { navigasi, auth } = usePage<PropsBersama>().props;
    const [buka, setBuka] = useState(false);
    const utama = navigasi?.utama ?? [];
    const kelasTautan = 'rounded-full px-3 py-2 text-sm font-semibold text-foreground/80 transition-colors hover:bg-accent hover:text-accent-foreground';

    return (
        <header className="sticky top-0 z-40 border-b border-border/70 bg-background/85 backdrop-blur supports-[backdrop-filter]:bg-background/70">
            <div className="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-6">
                <Link href="/" className="shrink-0 rounded-lg" aria-label="Beranda">
                    <Logo tinggi={48} />
                </Link>

                <nav aria-label="Navigasi utama" className="ml-4 hidden items-center gap-0.5 lg:flex">
                    {utama.map((item) =>
                        item.anak.length ? (
                            <DropdownMenu key={item.id}>
                                <DropdownMenuTrigger className={cn(kelasTautan, 'inline-flex items-center gap-1')}>
                                    {item.label} <ChevronDown className="size-3.5" aria-hidden />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent>
                                    {[item, ...item.anak].map((a) => (
                                        <DropdownMenuItem key={a.id} asChild>
                                            <TautanNav item={a} />
                                        </DropdownMenuItem>
                                    ))}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        ) : (
                            <TautanNav key={item.id} item={item} className={kelasTautan} />
                        ),
                    )}
                </nav>

                <div className="ml-auto flex items-center gap-1.5">
                    <FormCari className="hidden w-56 md:block xl:w-64" />
                    <PengalihTema />
                    <TombolAdmin masuk={auth.masuk} />
                    <Sheet open={buka} onOpenChange={setBuka}>
                        <SheetTrigger asChild>
                            <Button variant="ghost" size="icon" className="lg:hidden" aria-label="Buka menu navigasi">
                                <Menu className="size-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="right" className="w-[86vw] max-w-sm">
                            <SheetHeader>
                                <SheetTitle>Menu</SheetTitle>
                            </SheetHeader>
                            <div className="flex flex-col gap-4 px-4 pb-6">
                                <FormCari idInput="cari-drawer" />
                                <nav aria-label="Navigasi seluler" className="flex flex-col gap-1">
                                    {utama
                                        .flatMap((item) => [item, ...item.anak])
                                        .map((item) => (
                                            <TautanNav
                                                key={item.id}
                                                item={item}
                                                onClick={() => setBuka(false)}
                                                className="rounded-lg px-3 py-3 text-base font-semibold hover:bg-accent"
                                            />
                                        ))}
                                </nav>
                                <div className="border-t pt-4">
                                    <TombolAdmin masuk={auth.masuk} varian="drawer" onClick={() => setBuka(false)} />
                                    {!auth.masuk && (
                                        <p className="mt-2 text-center text-xs text-muted-foreground">Khusus pengelola website. Pembaca tidak perlu login.</p>
                                    )}
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>
    );
}
