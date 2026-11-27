import { usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PropsBersama } from '@/types';

/**
 * Logo dari pengaturan (terang/gelap) dengan cadangan lencana SVG bawaan proyek.
 * - Logo persegi panjang (rasio ≥ 1,4, biasanya sudah berisi tulisan): tampil utuh setinggi `tinggi` px,
 *   tanpa teks nama tambahan. Pada sidebar admin yang diciutkan diganti favicon persegi.
 * - Logo persegi/ikon: tampil 36 px diikuti nama aplikasi.
 */
export function Logo({ className, tampilkanNama = true, tinggi = 40 }: { className?: string; tampilkanNama?: boolean; tinggi?: number }) {
    const { situs } = usePage<PropsBersama>().props;
    const rasio = situs.logo_rasio ?? 1;

    if (situs.logo_terang && rasio >= 1.4) {
        const ukuran = { width: Math.round(tinggi * rasio), height: tinggi };
        const gaya = { height: tinggi, width: 'auto' };

        return (
            <span className={cn('inline-flex shrink-0 items-center', className)}>
                <img
                    src={situs.logo_terang}
                    alt={situs.nama_aplikasi}
                    {...ukuran}
                    style={gaya}
                    className={cn('max-w-none object-contain group-data-[collapsible=icon]:hidden', situs.logo_gelap && 'dark:hidden')}
                />
                {situs.logo_gelap && (
                    <img
                        src={situs.logo_gelap}
                        alt={situs.nama_aplikasi}
                        {...ukuran}
                        style={gaya}
                        className="hidden max-w-none object-contain group-data-[collapsible=icon]:hidden! dark:block"
                    />
                )}
                {/* Sidebar admin diciutkan: tanda persegi. */}
                <span className="hidden group-data-[collapsible=icon]:block">
                    {situs.favicon ? (
                        <img src={situs.favicon} alt={situs.nama_aplikasi} width={32} height={32} className="size-8 rounded-md object-contain" />
                    ) : (
                        <LencanaPing className="size-8" />
                    )}
                </span>
            </span>
        );
    }

    return (
        <span className={cn('inline-flex items-center gap-2.5', className)}>
            {situs.logo_terang ? (
                <>
                    <img
                        src={situs.logo_terang}
                        alt=""
                        width={36}
                        height={36}
                        className={cn('size-9 rounded-lg object-contain', situs.logo_gelap && 'dark:hidden')}
                    />
                    {situs.logo_gelap && (
                        <img src={situs.logo_gelap} alt="" width={36} height={36} className="hidden size-9 rounded-lg object-contain dark:block" />
                    )}
                </>
            ) : (
                <LencanaPing className="size-9" />
            )}
            {tampilkanNama && (
                <span className="flex flex-col leading-tight">
                    <span className="text-[0.95rem] font-extrabold tracking-tight">{situs.nama_aplikasi}</span>
                    {situs.tagline && <span className="hidden text-[0.7rem] font-medium text-muted-foreground sm:block">{situs.nama_singkat}</span>}
                </span>
            )}
        </span>
    );
}

export function LencanaPing({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 40 40" className={className} aria-hidden="true">
            <defs>
                <linearGradient id="lencana-ping" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stopColor="var(--merek)" />
                    <stop offset="1" stopColor="var(--merek-2)" />
                </linearGradient>
            </defs>
            <rect width="40" height="40" rx="11" fill="url(#lencana-ping)" />
            <path
                d="M12 28V12h7.2c3.4 0 5.8 2.2 5.8 5.3s-2.4 5.3-5.8 5.3H16"
                fill="none"
                stroke="#fff"
                strokeWidth="3.2"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <circle cx="29" cy="27" r="3" fill="#fff" />
        </svg>
    );
}
