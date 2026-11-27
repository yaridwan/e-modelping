import { motion, useReducedMotion } from 'motion/react';
import type { ReactNode } from 'react';
import { useEffect, useRef, useState } from 'react';

/**
 * Animasi masuk yang aman SSR & tanpa-JS: konten selalu terlihat di HTML awal.
 * Hanya elemen yang berada di luar layar saat mount yang disembunyikan sementara lalu
 * dianimasikan ketika terlihat. Menghormati prefers-reduced-motion.
 */
export function Muncul({
    children,
    className,
    jeda = 0,
    as = 'div',
}: {
    children: ReactNode;
    className?: string;
    jeda?: number;
    as?: 'div' | 'li' | 'section';
}) {
    const ref = useRef<HTMLDivElement>(null);
    const kurangiGerak = useReducedMotion();
    const [status, setStatus] = useState<'statis' | 'menunggu' | 'tampil'>('statis');

    useEffect(() => {
        const el = ref.current;
        if (!el || kurangiGerak || typeof IntersectionObserver === 'undefined') {
            return;
        }
        const kotak = el.getBoundingClientRect();
        if (kotak.top < window.innerHeight && kotak.bottom > 0) {
            return; // sudah terlihat saat mount: biarkan statis
        }
        setStatus('menunggu');
        const pengamat = new IntersectionObserver(
            (entri) => {
                if (entri.some((e) => e.isIntersecting)) {
                    setStatus('tampil');
                    pengamat.disconnect();
                }
            },
            { rootMargin: '0px 0px -8% 0px' },
        );
        pengamat.observe(el);

        return () => pengamat.disconnect();
    }, [kurangiGerak]);

    const Komponen = motion[as];

    return (
        <Komponen
            ref={ref as never}
            className={className}
            initial={false}
            animate={status === 'menunggu' ? { opacity: 0, y: 18 } : { opacity: 1, y: 0 }}
            transition={{ duration: status === 'tampil' ? 0.5 : 0, delay: status === 'tampil' ? jeda : 0, ease: [0.22, 1, 0.36, 1] }}
        >
            {children}
        </Komponen>
    );
}
