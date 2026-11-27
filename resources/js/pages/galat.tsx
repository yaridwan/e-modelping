import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

const PESAN: Record<number, { judul: string; isi: string }> = {
    403: { judul: 'Akses ditolak', isi: 'Anda tidak memiliki izin untuk membuka halaman atau berkas ini.' },
    404: { judul: 'Halaman tidak ditemukan', isi: 'Halaman yang Anda cari tidak tersedia, belum diterbitkan, atau sudah dipindahkan.' },
    500: { judul: 'Terjadi kesalahan', isi: 'Server mengalami kendala. Silakan coba beberapa saat lagi.' },
    503: { judul: 'Sedang pemeliharaan', isi: 'Website sedang dalam pemeliharaan singkat. Silakan kembali nanti.' },
};

export default function Galat({ status }: { status: number }) {
    const p = PESAN[status] ?? PESAN[500];

    return (
        <div className="mx-auto flex max-w-xl flex-col items-center px-4 py-24 text-center">
            <p className="text-7xl font-black text-primary/30">{status}</p>
            <h1 className="mt-4 text-3xl font-extrabold">{p.judul}</h1>
            <p className="mt-3 text-muted-foreground">{p.isi}</p>
            <div className="mt-8 flex flex-wrap justify-center gap-3">
                <Button asChild className="rounded-full">
                    <Link href="/">Ke beranda</Link>
                </Button>
                <Button asChild variant="outline" className="rounded-full">
                    <Link href="/modul">Daftar bab</Link>
                </Button>
            </div>
        </div>
    );
}
