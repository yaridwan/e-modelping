import { Eye, EyeOff } from 'lucide-react';
import type { ComponentProps, Ref } from 'react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

/** Kolom sandi dengan tombol lihat/sembunyikan yang dapat dijangkau keyboard. */
export default function PasswordInput({ className, ref, ...props }: Omit<ComponentProps<'input'>, 'type'> & { ref?: Ref<HTMLInputElement> }) {
    const [lihat, setLihat] = useState(false);

    return (
        <div className="relative">
            <Input type={lihat ? 'text' : 'password'} className={cn('pr-11', className)} ref={ref} {...props} />
            <button
                type="button"
                onClick={() => setLihat((v) => !v)}
                className="absolute inset-y-0 right-0 flex items-center rounded-r-md px-3 text-muted-foreground hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                aria-label={lihat ? 'Sembunyikan sandi' : 'Tampilkan sandi'}
                aria-pressed={lihat}
            >
                {lihat ? <EyeOff className="size-4" aria-hidden /> : <Eye className="size-4" aria-hidden />}
            </button>
        </div>
    );
}
