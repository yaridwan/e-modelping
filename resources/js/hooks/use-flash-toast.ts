import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { PropsBersama } from '@/types';

/** Tampilkan pesan sukses/galat dari sesi Laravel sebagai toast. */
export function useFlashToast(): void {
    const { flash } = usePage<PropsBersama>().props;

    useEffect(() => {
        if (flash?.sukses) {
            toast.success(flash.sukses);
        }
        if (flash?.galat) {
            toast.error(flash.galat);
        }
    }, [flash?.sukses, flash?.galat]);
}
