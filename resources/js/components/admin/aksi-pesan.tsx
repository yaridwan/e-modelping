import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { toast } from 'sonner';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { formatTanggal } from '@/lib/utils';

export type StatusPesan = 'baru' | 'dibaca' | 'ditindaklanjuti' | 'arsip';

/** Tautan mailto untuk membalas lewat aplikasi surel (aplikasi tidak mengirim surel sendiri). */
export function tautanBalas(p: { surel: string; subjek: string; nama?: string; isi?: string; dibuat_pada?: string | null }): string {
    const subjek = /^re:/i.test(p.subjek) ? p.subjek : `Re: ${p.subjek}`;
    let isi = '';
    if (p.isi) {
        // URL mailto yang terlalu panjang ditolak sebagian aplikasi surel; kutipan dibatasi.
        const kutipan = p.isi.length > 1200 ? `${p.isi.slice(0, 1200)}…` : p.isi;
        isi = `\n\n---\nPada ${formatTanggal(p.dibuat_pada ?? null, true)}, ${p.nama ?? p.surel} menulis:\n${kutipan
            .split('\n')
            .map((b) => `> ${b}`)
            .join('\n')}`;
    }

    return `mailto:${p.surel}?subject=${encodeURIComponent(subjek)}${isi ? `&body=${encodeURIComponent(isi)}` : ''}`;
}

export async function salinTeks(teks: string, label = 'Teks') {
    try {
        await navigator.clipboard.writeText(teks);
        toast.success(`${label} disalin.`);
    } catch {
        toast.error('Gagal menyalin. Salin secara manual.');
    }
}

/** Ubah status satu pesan (aksi cepat, catatan internal tidak berubah). */
export function ubahStatus(id: number, status: StatusPesan, selesai?: () => void) {
    router.patch(`/admin/pesan/${id}/status`, { status }, { preserveScroll: true, onFinish: selesai });
}

export type Konfirmasi = { judul: string; deskripsi: ReactNode; labelAksi: string; jalankan: (selesai: () => void) => void };

/** Dialog konfirmasi terkendali (dipakai untuk hapus permanen dari menu & aksi massal). */
export function DialogKonfirmasi({ konfirmasi, tutup }: { konfirmasi: Konfirmasi | null; tutup: () => void }) {
    const [proses, setProses] = useState(false);

    return (
        <AlertDialog open={!!konfirmasi} onOpenChange={(b) => !b && !proses && tutup()}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{konfirmasi?.judul}</AlertDialogTitle>
                    <AlertDialogDescription>{konfirmasi?.deskripsi}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={proses}>Batal</AlertDialogCancel>
                    <Button
                        variant="destructive"
                        disabled={proses}
                        onClick={() => {
                            setProses(true);
                            konfirmasi?.jalankan(() => {
                                setProses(false);
                                tutup();
                            });
                        }}
                    >
                        {proses ? 'Memproses…' : konfirmasi?.labelAksi}
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
