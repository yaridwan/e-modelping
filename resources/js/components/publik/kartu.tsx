import { Link } from '@inertiajs/react';
import { ArrowRight, Clock, FileCheck2, FileX2, Footprints } from 'lucide-react';
import { IkonDb } from '@/components/umum/ikon';
import { Muncul } from '@/components/umum/muncul';
import { cn } from '@/lib/utils';
import type { Aspek, BabRingkas, Fase } from '@/types';

const NAMA_FASE: Record<string, string> = { PCG: 'Fase I', ICTG: 'Fase II', EDCG: 'Fase III' };

export function KartuFase({
    fase,
    tampilkanDurasi = true,
    tampilkanLangkah = true,
    jeda = 0,
}: {
    fase: Fase;
    tampilkanDurasi?: boolean;
    tampilkanLangkah?: boolean;
    jeda?: number;
}) {
    return (
        <Muncul jeda={jeda} className="h-full">
            <article className="group relative flex h-full flex-col rounded-2xl border bg-card p-6 shadow-lembut transition-shadow hover:shadow-lg">
                <span className="text-xs font-bold tracking-widest text-primary uppercase">{NAMA_FASE[fase.kode] ?? `Fase ${fase.urutan}`}</span>
                <h3 className="mt-2 text-xl font-extrabold">
                    <Link href={fase.url} className="after:absolute after:inset-0 focus-visible:outline-none">
                        {fase.nama}
                    </Link>
                </h3>
                <p className="text-sm font-semibold text-muted-foreground">{fase.judul_indonesia}</p>
                <p className="mt-3 line-clamp-4 text-sm leading-relaxed text-foreground/80">{fase.deskripsi}</p>
                {tampilkanDurasi && (
                    <p className="mt-4 inline-flex w-fit items-center gap-1.5 rounded-full bg-secondary px-3 py-1 text-xs font-semibold text-secondary-foreground">
                        <Clock className="size-3.5" aria-hidden /> {fase.durasi}
                        {fase.kode === 'ICTG' && ' per perpindahan'}
                    </p>
                )}
                {tampilkanLangkah && fase.langkah_ringkas.length > 0 && (
                    <ol className="mt-4 space-y-1.5 text-sm">
                        {fase.langkah_ringkas.map((l, i) => (
                            <li key={l} className="flex gap-2">
                                <span className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[0.7rem] font-bold text-primary">
                                    {i + 1}
                                </span>
                                <span>{l}</span>
                            </li>
                        ))}
                    </ol>
                )}
                <span className="mt-auto inline-flex items-center gap-1 pt-5 text-sm font-bold text-primary">
                    Pelajari fase <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" aria-hidden />
                </span>
            </article>
        </Muncul>
    );
}

const WARNA_ASPEK: Record<string, string> = {
    pink: 'bg-pink-100 text-pink-800 dark:bg-pink-950 dark:text-pink-200',
    rose: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
    fuchsia: 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-950 dark:text-fuchsia-200',
    violet: 'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-200',
    amber: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    emerald: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    sky: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-200',
};

export function KartuAspek({ aspek, tampilkanIndikator = true, jeda = 0 }: { aspek: Aspek; tampilkanIndikator?: boolean; jeda?: number }) {
    return (
        <Muncul jeda={jeda} className="h-full">
            <article className="flex h-full flex-col rounded-2xl border bg-card p-6">
                <span className={cn('flex size-11 items-center justify-center rounded-xl', WARNA_ASPEK[aspek.warna ?? 'pink'] ?? WARNA_ASPEK.pink)}>
                    <IkonDb nama={aspek.ikon} className="size-5" />
                </span>
                <h3 className="mt-4 text-lg font-extrabold">{aspek.nama}</h3>
                {aspek.fokus_pengamatan && <p className="text-xs font-semibold text-muted-foreground">{aspek.fokus_pengamatan}</p>}
                <p className="mt-2 text-sm leading-relaxed text-foreground/80">{aspek.contoh_pembiasaan ?? aspek.deskripsi}</p>
                {tampilkanIndikator && aspek.indikator.length > 0 && (
                    <ul className="mt-4 space-y-1.5 border-t pt-4 text-sm">
                        {aspek.indikator.map((i) => (
                            <li key={i.nomor} className="flex gap-2">
                                <span className="font-bold text-primary tabular-nums">{i.nomor}.</span>
                                <span>{i.rumusan}</span>
                            </li>
                        ))}
                    </ul>
                )}
            </article>
        </Muncul>
    );
}

export function KartuBab({
    bab,
    tampilkanStatusDokumen = true,
    jeda = 0,
    varian = 'grid',
}: {
    bab: BabRingkas;
    tampilkanStatusDokumen?: boolean;
    jeda?: number;
    varian?: 'grid' | 'daftar';
}) {
    return (
        <Muncul jeda={jeda} className="h-full">
            <article
                className={cn(
                    'group relative flex h-full rounded-2xl border bg-card p-5 transition-colors hover:border-primary/50',
                    varian === 'grid' ? 'flex-col' : 'flex-col gap-4 sm:flex-row sm:items-center',
                )}
            >
                <span
                    className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[var(--merek)] to-[var(--merek-2)] text-lg font-black text-white"
                    aria-hidden
                >
                    {bab.romawi}
                </span>
                <div className={cn('flex flex-1 flex-col', varian === 'grid' && 'mt-4')}>
                    <p className="text-xs font-bold tracking-wider text-muted-foreground uppercase">Bab {bab.romawi}</p>
                    <h3 className="text-lg leading-snug font-extrabold">
                        <Link href={bab.url} className="after:absolute after:inset-0 focus-visible:outline-none">
                            {bab.judul}
                        </Link>
                    </h3>
                    {bab.ringkasan && <p className="mt-2 line-clamp-3 text-sm text-foreground/75">{bab.ringkasan}</p>}
                    <div className="mt-auto flex flex-wrap items-center gap-x-4 gap-y-1 pt-4 text-xs text-muted-foreground">
                        <span className="inline-flex items-center gap-1">
                            <Clock className="size-3.5" aria-hidden /> ±{bab.waktu_baca_menit} menit baca
                        </span>
                        <span className="inline-flex items-center gap-1">
                            <Footprints className="size-3.5" aria-hidden /> {bab.jumlah_subbab} subbab
                        </span>
                        {tampilkanStatusDokumen && (
                            <span className={cn('inline-flex items-center gap-1', bab.ada_dokumen ? 'text-emerald-700 dark:text-emerald-400' : '')}>
                                {bab.ada_dokumen ? <FileCheck2 className="size-3.5" aria-hidden /> : <FileX2 className="size-3.5" aria-hidden />}
                                {bab.ada_dokumen ? 'PDF tersedia' : 'PDF belum tersedia'}
                            </span>
                        )}
                    </div>
                </div>
            </article>
        </Muncul>
    );
}

export function JudulBagian({
    id,
    kecil,
    judul,
    deskripsi,
    rata = 'kiri',
}: {
    id?: string;
    kecil?: string;
    judul: string;
    deskripsi?: string | null;
    rata?: 'kiri' | 'tengah';
}) {
    return (
        <div className={cn('mb-10 max-w-2xl', rata === 'tengah' && 'mx-auto text-center')}>
            {kecil && <p className="text-sm font-bold tracking-widest text-primary uppercase">{kecil}</p>}
            <h2 id={id} className="mt-2 text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">
                {judul}
            </h2>
            {deskripsi && <p className="mt-3 text-base leading-relaxed text-muted-foreground">{deskripsi}</p>}
        </div>
    );
}
