import { AnimatePresence, motion } from 'motion/react';
import type { ReactNode } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

export function PesanGalat({ id, pesan }: { id?: string; pesan?: string }) {
    return (
        <AnimatePresence initial={false}>
            {pesan && (
                <motion.p
                    id={id}
                    role="alert"
                    initial={{ opacity: 0, y: -4 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0 }}
                    className="text-sm font-medium text-destructive"
                >
                    {pesan}
                </motion.p>
            )}
        </AnimatePresence>
    );
}

export function Bidang({
    id,
    label,
    galat,
    petunjuk,
    wajib,
    children,
    className,
}: {
    id: string;
    label: ReactNode;
    galat?: string;
    petunjuk?: ReactNode;
    wajib?: boolean;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('grid gap-1.5', className)}>
            <Label htmlFor={id}>
                {label}
                {wajib && (
                    <span className="text-destructive" aria-hidden>
                        {' '}
                        *
                    </span>
                )}
            </Label>
            {children}
            {petunjuk && !galat && (
                <p id={`${id}-petunjuk`} className="text-xs text-muted-foreground">
                    {petunjuk}
                </p>
            )}
            <PesanGalat id={`${id}-galat`} pesan={galat} />
        </div>
    );
}

type PropsTeks = {
    id: string;
    label: ReactNode;
    nilai: string | number | null | undefined;
    onUbah: (v: string) => void;
    galat?: string;
    petunjuk?: ReactNode;
    wajib?: boolean;
    tipe?: string;
    className?: string;
    placeholder?: string;
    maks?: number;
    autoComplete?: string;
};

export function BidangTeks({ id, label, nilai, onUbah, galat, petunjuk, wajib, tipe = 'text', className, placeholder, maks, autoComplete }: PropsTeks) {
    return (
        <Bidang id={id} label={label} galat={galat} petunjuk={petunjuk} wajib={wajib} className={className}>
            <Input
                id={id}
                type={tipe}
                value={nilai ?? ''}
                onChange={(e) => onUbah(e.target.value)}
                aria-invalid={!!galat}
                aria-describedby={galat ? `${id}-galat` : petunjuk ? `${id}-petunjuk` : undefined}
                required={wajib}
                placeholder={placeholder}
                maxLength={maks}
                autoComplete={autoComplete}
            />
        </Bidang>
    );
}

export function BidangTextarea({ id, label, nilai, onUbah, galat, petunjuk, wajib, className, baris = 4, maks }: Omit<PropsTeks, 'tipe'> & { baris?: number }) {
    return (
        <Bidang id={id} label={label} galat={galat} petunjuk={petunjuk} wajib={wajib} className={className}>
            <Textarea
                id={id}
                rows={baris}
                value={nilai ?? ''}
                onChange={(e) => onUbah(e.target.value)}
                aria-invalid={!!galat}
                aria-describedby={galat ? `${id}-galat` : petunjuk ? `${id}-petunjuk` : undefined}
                required={wajib}
                maxLength={maks}
            />
        </Bidang>
    );
}

export function BidangPilih({
    id,
    label,
    nilai,
    onUbah,
    opsi,
    galat,
    petunjuk,
    wajib,
    className,
    kosong,
}: {
    id: string;
    label: ReactNode;
    nilai: string | number | null | undefined;
    onUbah: (v: string) => void;
    opsi: { nilai: string | number; label: string }[];
    galat?: string;
    petunjuk?: ReactNode;
    wajib?: boolean;
    className?: string;
    kosong?: string;
}) {
    return (
        <Bidang id={id} label={label} galat={galat} petunjuk={petunjuk} wajib={wajib} className={className}>
            <select
                id={id}
                value={nilai ?? ''}
                onChange={(e) => onUbah(e.target.value)}
                aria-invalid={!!galat}
                required={wajib}
                className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive"
            >
                {kosong !== undefined && <option value="">{kosong}</option>}
                {opsi.map((o) => (
                    <option key={o.nilai} value={o.nilai}>
                        {o.label}
                    </option>
                ))}
            </select>
        </Bidang>
    );
}

export function BidangSakelar({
    id,
    label,
    nilai,
    onUbah,
    petunjuk,
    galat,
}: {
    id: string;
    label: ReactNode;
    nilai: boolean;
    onUbah: (v: boolean) => void;
    petunjuk?: ReactNode;
    galat?: string;
}) {
    return (
        <div className="flex items-start justify-between gap-4 rounded-lg border p-3">
            <div>
                <Label htmlFor={id}>{label}</Label>
                {petunjuk && <p className="mt-0.5 text-xs text-muted-foreground">{petunjuk}</p>}
                <PesanGalat pesan={galat} />
            </div>
            <Switch id={id} checked={nilai} onCheckedChange={onUbah} />
        </div>
    );
}

/** Editor daftar string (tujuan, poin penting, indikator keberhasilan). */
export function BidangDaftar({
    id,
    label,
    nilai,
    onUbah,
    galat,
    petunjuk,
    placeholder = 'Tambahkan butir…',
}: {
    id: string;
    label: ReactNode;
    nilai: string[];
    onUbah: (v: string[]) => void;
    galat?: string;
    petunjuk?: ReactNode;
    placeholder?: string;
}) {
    return (
        <Bidang id={`${id}-0`} label={label} galat={galat} petunjuk={petunjuk}>
            <ol className="space-y-2">
                {nilai.map((butir, i) => (
                    <li key={i} className="flex gap-2">
                        <span className="mt-2 w-5 shrink-0 text-right text-xs text-muted-foreground">{i + 1}.</span>
                        <Input
                            id={`${id}-${i}`}
                            value={butir}
                            onChange={(e) => onUbah(nilai.map((b, j) => (j === i ? e.target.value : b)))}
                            aria-label={`Butir ${i + 1}`}
                        />
                        <button
                            type="button"
                            onClick={() => onUbah(nilai.filter((_, j) => j !== i))}
                            className="shrink-0 rounded-md px-2 text-sm text-destructive hover:bg-destructive/10"
                            aria-label={`Hapus butir ${i + 1}`}
                        >
                            Hapus
                        </button>
                    </li>
                ))}
            </ol>
            <button
                type="button"
                onClick={() => onUbah([...nilai, ''])}
                className="mt-1 w-fit rounded-md border border-dashed px-3 py-1.5 text-sm text-muted-foreground hover:border-primary hover:text-primary"
            >
                + {placeholder}
            </button>
        </Bidang>
    );
}
