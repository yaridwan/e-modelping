import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';

/** Tombol aksi dengan dialog konfirmasi (hapus, arsip, terbitkan). */
export function TombolKonfirmasi({
    url,
    metode = 'delete',
    data,
    judul,
    deskripsi,
    labelAksi = 'Hapus',
    varian = 'destructive',
    children,
    ukuran = 'sm',
}: {
    url: string;
    metode?: 'delete' | 'post' | 'patch' | 'put';
    data?: Record<string, unknown>;
    judul: string;
    deskripsi: ReactNode;
    labelAksi?: string;
    varian?: 'destructive' | 'default' | 'outline' | 'secondary' | 'ghost';
    children: ReactNode;
    ukuran?: 'sm' | 'default' | 'icon';
}) {
    const [buka, setBuka] = useState(false);
    const [proses, setProses] = useState(false);

    return (
        <AlertDialog open={buka} onOpenChange={setBuka}>
            <AlertDialogTrigger asChild>
                <Button
                    variant={varian === 'destructive' ? 'outline' : varian}
                    size={ukuran}
                    className={varian === 'destructive' ? 'text-destructive hover:text-destructive' : undefined}
                >
                    {children}
                </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{judul}</AlertDialogTitle>
                    <AlertDialogDescription>{deskripsi}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={proses}>Batal</AlertDialogCancel>
                    <Button
                        variant={varian === 'destructive' ? 'destructive' : 'default'}
                        disabled={proses}
                        onClick={() =>
                            router.visit(url, {
                                method: metode,
                                data: data as never,
                                preserveScroll: true,
                                onStart: () => setProses(true),
                                onFinish: () => {
                                    setProses(false);
                                    setBuka(false);
                                },
                            })
                        }
                    >
                        {proses ? 'Memproses…' : labelAksi}
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
