import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';

export type JejakPublik = { nama: string; url?: string };

/** Breadcrumb + H1 halaman publik. */
export function KepalaHalaman({
    jejak,
    judul,
    kecil,
    deskripsi,
    children,
}: {
    jejak: JejakPublik[];
    judul: string;
    kecil?: ReactNode;
    deskripsi?: ReactNode;
    children?: ReactNode;
}) {
    return (
        <div className="relative overflow-hidden border-b bg-card">
            <div className="pointer-events-none absolute inset-0 latar-titik opacity-50" aria-hidden />
            <div className="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14">
                <nav aria-label="Breadcrumb">
                    <ol className="flex flex-wrap items-center gap-1 text-sm text-muted-foreground">
                        {jejak.map((j, i) => (
                            <li key={j.nama} className="flex items-center gap-1">
                                {i > 0 && <ChevronRight className="size-3.5" aria-hidden />}
                                {j.url ? (
                                    <Link href={j.url} className="hover:text-primary">
                                        {j.nama}
                                    </Link>
                                ) : (
                                    <span aria-current="page" className="font-medium text-foreground">
                                        {j.nama}
                                    </span>
                                )}
                            </li>
                        ))}
                    </ol>
                </nav>
                {kecil && <p className="mt-6 text-sm font-bold tracking-widest text-primary uppercase">{kecil}</p>}
                <h1 className="mt-2 max-w-4xl text-3xl font-black tracking-tight text-balance sm:text-5xl">{judul}</h1>
                {deskripsi && <div className="mt-4 max-w-3xl text-lg leading-relaxed text-muted-foreground">{deskripsi}</div>}
                {children}
            </div>
        </div>
    );
}
