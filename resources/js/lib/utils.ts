import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

const ZONA = 'Asia/Jakarta';

/** Format tanggal Indonesia. Aman SSR (zona waktu eksplisit, tanpa akses window). */
export function formatTanggal(iso: string | null | undefined, denganJam = false): string {
    if (!iso) {
        return '-';
    }
    const t = new Date(iso.length === 10 ? `${iso}T00:00:00+07:00` : iso);
    if (Number.isNaN(t.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        ...(denganJam ? { hour: '2-digit', minute: '2-digit' } : {}),
        timeZone: ZONA,
    }).format(t);
}

export function formatTanggalPendek(iso: string): string {
    const t = new Date(`${iso.slice(0, 10)}T00:00:00+07:00`);

    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', timeZone: ZONA }).format(t);
}

export function formatUkuran(bita: number | null | undefined): string {
    if (!bita) {
        return '-';
    }
    const satuan = ['B', 'KB', 'MB', 'GB'];
    let n = bita;
    let i = 0;
    while (n >= 1024 && i < satuan.length - 1) {
        n /= 1024;
        i++;
    }

    return `${n.toLocaleString('id-ID', { maximumFractionDigits: i === 0 ? 0 : 1 })} ${satuan[i]}`;
}

export function formatAngka(n: number | null | undefined): string {
    return (n ?? 0).toLocaleString('id-ID');
}

/** localStorage yang aman (mode privat, situs diblokir, SSR). */
export const penyimpanan = {
    ambil<T>(kunci: string, bawaan: T): T {
        if (typeof window === 'undefined') {
            return bawaan;
        }
        try {
            const nilai = window.localStorage.getItem(kunci);

            return nilai === null ? bawaan : (JSON.parse(nilai) as T);
        } catch {
            return bawaan;
        }
    },
    simpan(kunci: string, nilai: unknown): void {
        try {
            window.localStorage.setItem(kunci, JSON.stringify(nilai));
        } catch {
            /* penyimpanan tidak tersedia */
        }
    },
    hapus(kunci: string): void {
        try {
            window.localStorage.removeItem(kunci);
        } catch {
            /* abaikan */
        }
    },
};

export const LABEL_STATUS: Record<string, string> = {
    draf: 'Draf',
    terbit: 'Terbit',
    arsip: 'Arsip',
    baru: 'Baru',
    dibaca: 'Dibaca',
    ditindaklanjuti: 'Ditindaklanjuti',
};
