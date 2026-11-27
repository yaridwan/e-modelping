<?php

namespace App\Layanan;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitasi HTML dari editor teks kaya. Hanya elemen semantik untuk materi baca yang diizinkan.
 * Skrip, iframe, atribut event, style, dan skema URL berbahaya (javascript:, data:, vbscript:) dibuang.
 */
class LayananSanitasiHtml
{
    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        $config = (new HtmlSanitizerConfig)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('hr')
            ->allowElement('h2', ['id'])
            ->allowElement('h3', ['id'])
            ->allowElement('h4', ['id'])
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('sub')
            ->allowElement('sup')
            ->allowElement('mark')
            ->allowElement('code')
            ->allowElement('pre')
            ->allowElement('blockquote')
            ->allowElement('ul')
            ->allowElement('ol', ['type', 'start'])
            ->allowElement('li')
            ->allowElement('a', ['href', 'title', 'target', 'rel'])
            ->allowElement('img', ['src', 'alt', 'width', 'height', 'title'])
            ->allowElement('figure')
            ->allowElement('figcaption')
            ->allowElement('aside', ['class'])
            ->allowElement('table')
            ->allowElement('caption')
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tfoot')
            ->allowElement('tr')
            ->allowElement('th', ['colspan', 'rowspan', 'scope'])
            ->allowElement('td', ['colspan', 'rowspan'])
            ->allowElement('colgroup')
            ->allowElement('col', ['span'])
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['https', 'http'])
            ->allowRelativeMedias()
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->withMaxInputLength(2_000_000);

        if ($host) {
            $config = $config->allowMediaHosts([$host, '127.0.0.1', 'localhost']);
        }

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function bersihkan(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $bersih = trim($this->sanitizer->sanitize($html));
        // Hanya izinkan class "rujukan-sumber" pada aside (dipakai materi hasil impor).
        $bersih = preg_replace('/<aside class="(?!rujukan-sumber")[^"]*"/', '<aside', $bersih) ?? $bersih;

        return $bersih === '' ? null : $bersih;
    }

    /** Teks polos dari HTML (untuk pencarian, meta deskripsi, dan waktu baca). */
    public static function teksPolos(?string $html): string
    {
        $teks = html_entity_decode(strip_tags(str_replace(['</p>', '</li>', '</h2>', '</h3>', '</h4>', '<br>', '</td>'], ' ', (string) $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $teks) ?? '');
    }

    /** Validasi URL tautan yang dimasukkan admin (menu, CTA, sosial). */
    public static function urlAman(?string $url, bool $izinkanRelatif = true): bool
    {
        if ($url === null || $url === '') {
            return true;
        }
        $url = trim($url);
        if ($izinkanRelatif && str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return ! preg_match('/[\s<>"\'\\\\]/', $url);
        }
        if ($izinkanRelatif && str_starts_with($url, '#')) {
            return (bool) preg_match('/^#[A-Za-z0-9\-_]*$/', $url);
        }
        $skema = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($skema, ['http', 'https', 'mailto'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false
            || ($skema === 'mailto' && filter_var(substr($url, 7), FILTER_VALIDATE_EMAIL) !== false);
    }
}
