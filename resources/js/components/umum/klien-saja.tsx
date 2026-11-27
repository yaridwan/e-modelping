import type { ReactNode } from 'react';
import { Suspense, useEffect, useState } from 'react';

/**
 * Merender komponen khusus browser (grafik, penampil PDF) hanya setelah mount.
 * Saat SSR dan render pertama, fallback ditampilkan sehingga tidak ada Suspense malas
 * yang gagal di server (React error #419) dan tidak ada hydration mismatch.
 */
export function KlienSaja({ children, fallback }: { children: ReactNode; fallback: ReactNode }) {
    const [siap, setSiap] = useState(false);
    useEffect(() => setSiap(true), []);

    return siap ? <Suspense fallback={fallback}>{children}</Suspense> : <>{fallback}</>;
}
