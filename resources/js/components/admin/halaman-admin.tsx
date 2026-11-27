import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { motion } from 'motion/react';
import type { ReactNode } from 'react';

export type Jejak = { judul: string; href?: string };

/** Kerangka halaman admin: breadcrumb, judul (H1), deskripsi, dan aksi. */
export function HalamanAdmin({
    judul,
    deskripsi,
    jejak = [],
    aksi,
    children,
}: {
    judul: string;
    deskripsi?: ReactNode;
    jejak?: Jejak[];
    aksi?: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="space-y-6">
            <div className="space-y-3">
                <nav aria-label="Breadcrumb">
                    <ol className="flex flex-wrap items-center gap-1 text-xs text-muted-foreground">
                        <li>
                            <Link href="/admin" className="hover:text-primary">
                                Admin
                            </Link>
                        </li>
                        {jejak.map((j) => (
                            <li key={j.judul} className="flex items-center gap-1">
                                <ChevronRight className="size-3" aria-hidden />
                                {j.href ? (
                                    <Link href={j.href} className="hover:text-primary">
                                        {j.judul}
                                    </Link>
                                ) : (
                                    <span aria-current="page">{j.judul}</span>
                                )}
                            </li>
                        ))}
                    </ol>
                </nav>
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-extrabold tracking-tight">{judul}</h1>
                        {deskripsi && <p className="mt-1 max-w-3xl text-sm text-muted-foreground">{deskripsi}</p>}
                    </div>
                    {aksi && <div className="flex flex-wrap gap-2">{aksi}</div>}
                </div>
            </div>
            <motion.div initial={false} animate={{ opacity: 1 }}>
                {children}
            </motion.div>
        </div>
    );
}

export function KartuAdmin({
    judul,
    deskripsi,
    aksi,
    children,
    className = '',
}: {
    judul?: ReactNode;
    deskripsi?: ReactNode;
    aksi?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section className={`rounded-xl border bg-card shadow-xs ${className}`}>
            {(judul || aksi) && (
                <div className="flex flex-wrap items-start justify-between gap-2 border-b px-5 py-4">
                    <div>
                        {judul && <h2 className="font-bold">{judul}</h2>}
                        {deskripsi && <p className="mt-0.5 text-sm text-muted-foreground">{deskripsi}</p>}
                    </div>
                    {aksi}
                </div>
            )}
            <div className="p-5">{children}</div>
        </section>
    );
}
