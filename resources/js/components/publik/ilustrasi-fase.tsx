import { useEffect, useRef } from 'react';

/**
 * Ilustrasi SVG alur tiga fase PING (aset proyek, bukan unggahan). Animasi Anime.js
 * terisolasi pada elemen ini: dimuat dinamis hanya di beranda, dibersihkan saat unmount,
 * dijeda saat tidak terlihat, dan tidak berjalan bila pengguna meminta gerak dikurangi.
 * Motion tidak menyentuh properti elemen di dalam SVG ini.
 */
export function IlustrasiFase({ label }: { label: string }) {
    const akar = useRef<SVGSVGElement>(null);

    useEffect(() => {
        const el = akar.current;
        if (!el || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        let batal = false;
        let bersihkan: (() => void) | undefined;

        void import('animejs').then(({ animate, createScope, svg, stagger }) => {
            if (batal || !el) {
                return;
            }
            const lingkup = createScope({ root: el }).add(() => {
                const jalur = animate(svg.createDrawable('.jalur-ping'), {
                    draw: ['0 0', '0 1'],
                    duration: 2600,
                    ease: 'inOutQuad',
                    loop: true,
                    loopDelay: 1400,
                    alternate: true,
                });
                const titik = animate('.titik-fase', {
                    scale: [1, 1.12],
                    duration: 900,
                    delay: stagger(450),
                    ease: 'inOutSine',
                    loop: true,
                    alternate: true,
                });
                const awan = animate('.awan', { translateX: [0, 14], duration: 5000, ease: 'inOutSine', loop: true, alternate: true });
                const semua = [jalur, titik, awan];
                const pengamat = new IntersectionObserver((e) => semua.forEach((a) => (e[0]?.isIntersecting ? a.play() : a.pause())));
                pengamat.observe(el);

                return () => pengamat.disconnect();
            });
            bersihkan = () => lingkup.revert();
        });

        return () => {
            batal = true;
            bersihkan?.();
        };
    }, []);

    return (
        <svg ref={akar} viewBox="0 0 480 400" role="img" aria-label={label} className="h-auto w-full max-w-lg">
            <defs>
                <linearGradient id="latar-ilustrasi" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stopColor="var(--merek-2)" stopOpacity="0.16" />
                    <stop offset="1" stopColor="var(--merek)" stopOpacity="0.08" />
                </linearGradient>
            </defs>
            <rect x="10" y="20" width="460" height="360" rx="36" fill="url(#latar-ilustrasi)" />
            <g className="awan" fill="currentColor" opacity="0.08">
                <ellipse cx="110" cy="70" rx="46" ry="16" />
                <ellipse cx="360" cy="56" rx="38" ry="13" />
            </g>
            <path
                className="jalur-ping"
                d="M70 300 C 140 300, 140 200, 220 200 S 320 110, 400 110"
                fill="none"
                stroke="var(--merek-2)"
                strokeWidth="10"
                strokeLinecap="round"
                strokeDasharray="1 0"
                opacity="0.85"
            />
            {[
                { x: 70, y: 300, label: 'Pre-Class', sub: 'Datang', ikon: 'M-9 4 L0 -6 L9 4 Z' },
                { x: 220, y: 200, label: 'Inter-Class', sub: 'Berpindah', ikon: 'M-9 0 H9 M3 -6 L9 0 L3 6' },
                { x: 400, y: 110, label: 'End-Day', sub: 'Pulang', ikon: 'M-7 0 a7 7 0 1 0 14 0 a7 7 0 1 0 -14 0' },
            ].map((t, i) => (
                <g key={t.label} transform={`translate(${t.x} ${t.y})`}>
                    <g className="titik-fase" style={{ transformOrigin: '0 0', transformBox: 'fill-box' }}>
                        <circle r="30" fill="var(--card)" stroke="var(--merek)" strokeWidth="4" />
                        <path d={t.ikon} fill="none" stroke="var(--merek)" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round" />
                    </g>
                    <text y={i === 2 ? -44 : 54} textAnchor="middle" fontSize="15" fontWeight="800" fill="currentColor">
                        {t.label}
                    </text>
                    <text y={i === 2 ? -27 : 72} textAnchor="middle" fontSize="12" fill="currentColor" opacity="0.7">
                        {t.sub}
                    </text>
                </g>
            ))}
            <g transform="translate(300 290)" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" opacity="0.55">
                <circle cx="0" cy="-26" r="10" />
                <path d="M0 -16 V14 M0 14 L-10 34 M0 14 L10 34 M0 -6 L-14 6 M0 -6 L16 -14" />
                <circle cx="44" cy="-14" r="7" />
                <path d="M44 -7 V16 M44 16 L37 30 M44 16 L51 30 M44 -1 L30 -10" />
            </g>
        </svg>
    );
}
