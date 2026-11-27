import inertia from '@inertiajs/vite';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            // Font diunduh saat build dan disajikan dari server sendiri (tanpa permintaan pihak ketiga saat runtime).
            fonts: [
                bunny('Plus Jakarta Sans', {
                    weights: [400, 500, 600, 700, 800],
                }),
            ],
        }),
        // SSR: plugin Inertia memakai app.tsx sebagai entri SSR (bootstrap/ssr/ssr.js saat build --ssr).
        inertia({
            ssr: {
                host: '127.0.0.1',
                port: 13714,
            },
        }),
        react({ compiler: true }),
        tailwindcss(),
    ]),
    build: {
        chunkSizeWarningLimit: 1200,
    },
    server: {
        // IPv4 eksplisit: alamat IPv6 seperti http://[::1]:5173 tidak sah di Content-Security-Policy,
        // sehingga aset dev (CSS, font, worker PDF.js) akan diblokir browser.
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        watch: {
            ignored: ['**/.agents/**', '**/.claude/**', '**/vendor/**', '**/storage/**', '**/database/seeders/aset/**'],
        },
    },
    lint: {
        ignorePatterns: ['vendor/**', 'node_modules/**', 'public/**', 'bootstrap/ssr/**', 'resources/js/components/ui/*', 'scripts/**'],
        options: {
            denyWarnings: false,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 160,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: ['.github/**', 'composer.json', 'resources/js/components/ui/*', 'resources/views/**', 'database/**', 'scripts/**'],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
