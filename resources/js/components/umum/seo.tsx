import { Head, usePage } from '@inertiajs/react';
import type { PropsBersama } from '@/types';

/**
 * Metadata per halaman untuk navigasi sisi klien. Respons awal (SSR/HTML) sudah berisi tag
 * yang sama dari root template Blade dengan atribut data-inertia yang sama.
 */
export function Seo({ judulAdmin }: { judulAdmin?: string }) {
    const { seo, situs } = usePage<PropsBersama>().props;

    if (!seo) {
        return (
            <Head>
                <title>{judulAdmin ? `${judulAdmin} · ${situs.nama_aplikasi}` : situs.nama_aplikasi}</title>
                <meta head-key="robots" name="robots" content="noindex,nofollow" />
            </Head>
        );
    }

    return (
        <Head>
            <title>{seo.judul}</title>
            <meta head-key="description" name="description" content={seo.deskripsi} />
            <meta head-key="robots" name="robots" content={seo.robots} />
            <link head-key="canonical" rel="canonical" href={seo.canonical} />
            <meta head-key="og:type" property="og:type" content={seo.og.type} />
            <meta head-key="og:site_name" property="og:site_name" content={seo.og.site_name} />
            <meta head-key="og:locale" property="og:locale" content={seo.og.locale} />
            <meta head-key="og:title" property="og:title" content={seo.judul} />
            <meta head-key="og:description" property="og:description" content={seo.deskripsi} />
            <meta head-key="og:url" property="og:url" content={seo.canonical} />
            <meta head-key="og:image" property="og:image" content={seo.og.image} />
            <meta head-key="og:image:alt" property="og:image:alt" content={seo.og.image_alt} />
            <meta head-key="twitter:card" name="twitter:card" content={seo.twitter.card} />
            <meta head-key="twitter:title" name="twitter:title" content={seo.judul} />
            <meta head-key="twitter:description" name="twitter:description" content={seo.deskripsi} />
            <meta head-key="twitter:image" name="twitter:image" content={seo.og.image} />
        </Head>
    );
}
