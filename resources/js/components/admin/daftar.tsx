import { Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown, Inbox, Search } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { cn, LABEL_STATUS } from '@/lib/utils';
import type { Paginasi } from '@/types';

type Filter = Record<string, string | number | boolean | null | undefined>;

function bersihkan(f: Filter): Record<string, string> {
    const hasil: Record<string, string> = {};
    Object.entries(f).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '' && v !== false) {
            hasil[k] = String(v === true ? 1 : v);
        }
    });

    return hasil;
}

/** Terapkan filter daftar ke URL (pagination & query di server). */
export function terapkanFilter(url: string, filter: Filter) {
    router.get(url, bersihkan({ ...filter, page: undefined }), { preserveState: true, preserveScroll: true, replace: true });
}

export function KotakCari({
    url,
    filter,
    placeholder = 'Cari…',
    children,
}: {
    url: string;
    filter: Filter & { q?: string };
    placeholder?: string;
    children?: ReactNode;
}) {
    const [q, setQ] = useState(filter.q ?? '');
    const pertama = useRef(true);
    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }
        const t = setTimeout(() => terapkanFilter(url, { ...filter, q }), 350);

        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [q]);

    return (
        <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
            <div className="relative sm:w-72">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden />
                <label htmlFor="cari-daftar" className="sr-only">
                    {placeholder}
                </label>
                <input
                    id="cari-daftar"
                    type="search"
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                    placeholder={placeholder}
                    className="h-9 w-full rounded-md border border-input bg-background pr-3 pl-9 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
            </div>
            {children}
        </div>
    );
}

export function PilihFilter({
    label,
    nilai,
    opsi,
    onUbah,
}: {
    label: string;
    nilai: string | null | undefined;
    opsi: { nilai: string; label: string }[];
    onUbah: (v: string) => void;
}) {
    const id = `filter-${label.toLowerCase().replace(/\W+/g, '-')}`;

    return (
        <div className="flex items-center gap-2">
            <label htmlFor={id} className="text-sm text-muted-foreground">
                {label}
            </label>
            <select
                id={id}
                value={nilai ?? ''}
                onChange={(e) => onUbah(e.target.value)}
                className="h-9 rounded-md border border-input bg-background px-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
            >
                <option value="">Semua</option>
                {opsi.map((o) => (
                    <option key={o.nilai} value={o.nilai}>
                        {o.label}
                    </option>
                ))}
            </select>
        </div>
    );
}

export function KepalaUrut({
    url,
    filter,
    kolom,
    children,
}: {
    url: string;
    filter: Filter & { urut?: string; arah?: string };
    kolom: string;
    children: ReactNode;
}) {
    const aktif = filter.urut === kolom;
    const arahBaru = aktif && filter.arah === 'asc' ? 'desc' : 'asc';
    const Ikon = !aktif ? ArrowUpDown : filter.arah === 'asc' ? ArrowUp : ArrowDown;

    return (
        <button
            type="button"
            onClick={() => terapkanFilter(url, { ...filter, urut: kolom, arah: arahBaru })}
            className="inline-flex items-center gap-1 uppercase hover:text-foreground"
            aria-label={`Urutkan berdasarkan ${typeof children === 'string' ? children : kolom}`}
            aria-sort={aktif ? (filter.arah === 'asc' ? 'ascending' : 'descending') : undefined}
        >
            {children}
            <Ikon className="size-3" aria-hidden />
        </button>
    );
}

export function Halaman<T>({ data }: { data: Paginasi<T> }) {
    if (data.last_page <= 1) {
        return data.total ? <p className="mt-3 text-sm text-muted-foreground">{data.total.toLocaleString('id-ID')} data</p> : null;
    }

    return (
        <nav aria-label="Navigasi halaman" className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p className="text-sm text-muted-foreground">
                Menampilkan {data.from}–{data.to} dari {data.total.toLocaleString('id-ID')} data
            </p>
            <ul className="flex flex-wrap gap-1">
                {data.links.map((l, i) => {
                    const label = l.label.replace('&laquo;', '‹').replace('&raquo;', '›').replace('Previous', 'Sebelumnya').replace('Next', 'Berikutnya');

                    return (
                        <li key={i}>
                            {l.url ? (
                                <Link
                                    href={l.url}
                                    preserveScroll
                                    preserveState
                                    className={cn(
                                        'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm',
                                        l.active ? 'border-primary bg-primary text-primary-foreground' : 'hover:bg-accent',
                                    )}
                                    aria-current={l.active ? 'page' : undefined}
                                >
                                    {label}
                                </Link>
                            ) : (
                                <span className="inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm text-muted-foreground opacity-50">
                                    {label}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}

export function LencanaStatus({ status }: { status: string }) {
    const gaya: Record<string, string> = {
        terbit: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
        draf: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
        arsip: 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
        baru: 'bg-pink-100 text-pink-800 dark:bg-pink-950 dark:text-pink-200',
        dibaca: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-200',
        ditindaklanjuti: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    };

    return <Badge className={cn('border-0 font-semibold', gaya[status] ?? 'bg-muted text-muted-foreground')}>{LABEL_STATUS[status] ?? status}</Badge>;
}

export function KeadaanKosong({ judul = 'Belum ada data', deskripsi, aksi }: { judul?: string; deskripsi?: ReactNode; aksi?: ReactNode }) {
    return (
        <div className="flex flex-col items-center justify-center rounded-xl border border-dashed px-6 py-12 text-center">
            <Inbox className="mb-3 size-10 text-muted-foreground" aria-hidden />
            <p className="font-semibold">{judul}</p>
            {deskripsi && <p className="mt-1 max-w-md text-sm text-muted-foreground">{deskripsi}</p>}
            {aksi && <div className="mt-4">{aksi}</div>}
        </div>
    );
}

export function LencanaDemo() {
    return <Badge className="border-0 bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-200">Data demo</Badge>;
}
