<?php

namespace App\Layanan;

use App\Models\BerkasMedia;
use App\Models\Pengguna;
use Illuminate\Support\Str;

/**
 * Pembangun metadata SEO. Canonical selalu absolut dari APP_URL tepercaya (bukan header Host).
 */
class LayananSeo
{
    public function __construct(private readonly LayananPengaturan $pengaturan) {}

    public static function urlDasar(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public static function absolut(?string $jalur): ?string
    {
        if (! $jalur) {
            return null;
        }

        return preg_match('#^https?://#', $jalur) ? $jalur : self::urlDasar().'/'.ltrim($jalur, '/');
    }

    /**
     * @param  array{judul?: ?string, deskripsi?: ?string, jalur?: string, gambar?: ?BerkasMedia, robots?: string, jenis?: string, jsonLd?: list<array>, judul_mentah?: bool}  $opsi
     * @return array<string, mixed>
     */
    public function bangun(array $opsi): array
    {
        $p = $this->pengaturan->ambil();
        $nama = $p->nama_aplikasi ?: 'E-Model PING';
        $judulHalaman = $opsi['judul'] ?? null;
        $judul = $judulHalaman
            ? (($opsi['judul_mentah'] ?? false) ? $judulHalaman : str_replace(['%judul%', '%nama%'], [$judulHalaman, $nama], $p->templat_judul_seo ?: '%judul% | %nama%'))
            : ($p->judul_seo_bawaan ?: $nama.($p->tagline ? ' — '.$p->tagline : ''));
        $deskripsi = Str::limit(LayananSanitasiHtml::teksPolos($opsi['deskripsi'] ?? null) ?: ($p->deskripsi_seo_bawaan ?: (string) $p->deskripsi_singkat), 300, '…');
        $gambar = $opsi['gambar'] ?? $p->gambarOg ?? $p->gambarSampul;
        $canonical = self::urlDasar().($opsi['jalur'] ?? '/');

        return [
            'judul' => $judul,
            'deskripsi' => $deskripsi,
            'canonical' => $canonical,
            'robots' => $opsi['robots'] ?? 'index,follow',
            'og' => [
                'type' => $opsi['jenis'] ?? 'website',
                'site_name' => $nama,
                'locale' => 'id_ID',
                'image' => $gambar ? self::absolut($gambar->urlPublik()) : self::absolut('/images/og-bawaan.png'),
                'image_alt' => $gambar?->teks_alternatif ?: $nama,
            ],
            'twitter' => ['card' => 'summary_large_image'],
            'verifikasi' => array_filter([
                'google' => $p->kode_verifikasi_google,
                'bing' => $p->kode_verifikasi_bing,
            ]),
            'json_ld' => $opsi['jsonLd'] ?? [],
        ];
    }

    /** JSON-LD WebSite + penerbit (Person/Organization) sesuai identitas yang tersedia. */
    public function jsonLdSitus(?Pengguna $pemilikPublik): array
    {
        $p = $this->pengaturan->ambil();
        $dasar = self::urlDasar();
        $penerbit = $this->penerbit($pemilikPublik);

        return array_values(array_filter([
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $p->nama_aplikasi,
                'alternateName' => $p->nama_singkat,
                'description' => $p->deskripsi_singkat,
                'url' => $dasar.'/',
                'inLanguage' => 'id-ID',
                'publisher' => $penerbit,
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $dasar.'/cari?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ]));
    }

    public function penerbit(?Pengguna $pemilikPublik): ?array
    {
        $p = $this->pengaturan->ambil();
        if ($p->jenis_identitas_penerbit === 'organisasi' && $p->nama_institusi) {
            return array_filter(['@type' => 'Organization', 'name' => $p->nama_institusi, 'url' => self::urlDasar().'/', 'logo' => $p->logoTerang ? self::absolut($p->logoTerang->urlPublik()) : null]);
        }
        $nama = $pemilikPublik?->nama_lengkap ?: $p->nama_pemilik;

        return $nama ? array_filter(['@type' => 'Person', 'name' => $nama, 'url' => self::urlDasar().'/tentang', 'jobTitle' => $pemilikPublik?->jabatan]) : null;
    }

    /** @param list<array{nama: string, url: string}> $jejak */
    public static function jsonLdBreadcrumb(array $jejak): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($jejak)->values()->map(fn ($j, $i) => [
                '@type' => 'ListItem', 'position' => $i + 1, 'name' => $j['nama'], 'item' => self::absolut($j['url']),
            ])->all(),
        ];
    }
}
