import { cn } from '@/lib/utils';

export function Progress({ nilai, className }: { nilai: number; className?: string }) {
    return (
        <div
            className={cn('h-2 w-full overflow-hidden rounded-full bg-muted', className)}
            role="progressbar"
            aria-valuenow={Math.round(nilai)}
            aria-valuemin={0}
            aria-valuemax={100}
            aria-label="Kemajuan unggahan"
        >
            <div className="h-full bg-primary transition-[width]" style={{ width: `${nilai}%` }} />
        </div>
    );
}
