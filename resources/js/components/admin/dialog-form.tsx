import type { ReactNode } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';

/** Dialog formulir tambah/sunting untuk daftar sederhana (FAQ, referensi, menu, pengalihan). */
export function DialogForm({
    buka,
    onTutup,
    judul,
    deskripsi,
    children,
}: {
    buka: boolean;
    onTutup: () => void;
    judul: string;
    deskripsi?: string;
    children: ReactNode;
}) {
    return (
        <Dialog open={buka} onOpenChange={(v) => !v && onTutup()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{judul}</DialogTitle>
                    {deskripsi && <DialogDescription>{deskripsi}</DialogDescription>}
                </DialogHeader>
                {children}
            </DialogContent>
        </Dialog>
    );
}
