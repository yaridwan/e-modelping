<?php

namespace App\Layanan;

use Illuminate\Http\Request;

/**
 * Sidik pseudonim pengunjung: HMAC dari IP yang dipotong (oktet terakhir IPv4 / 80 bit akhir IPv6
 * dinolkan) dan user agent, dengan kunci aplikasi. IP mentah tidak disimpan. Sidik adalah
 * perkiraan, bukan identitas orang unik absolut.
 */
class LayananSidik
{
    public function pengunjung(Request $request): string
    {
        return hash_hmac('sha256', $this->ipTersamar($request->ip()).'|'.substr((string) $request->userAgent(), 0, 300), (string) config('app.key'));
    }

    public function perangkat(Request $request): string
    {
        $ua = strtolower((string) $request->userAgent());
        if ($ua === '') {
            return 'lainnya';
        }
        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet') || (str_contains($ua, 'android') && ! str_contains($ua, 'mobile'))) {
            return 'tablet';
        }
        if (str_contains($ua, 'mobi') || str_contains($ua, 'iphone') || str_contains($ua, 'android')) {
            return 'ponsel';
        }
        if (str_contains($ua, 'windows') || str_contains($ua, 'macintosh') || str_contains($ua, 'linux') || str_contains($ua, 'cros')) {
            return 'desktop';
        }

        return 'lainnya';
    }

    public function bot(Request $request): bool
    {
        $ua = (string) $request->userAgent();

        return $ua === '' || (bool) preg_match((string) config('ping.statistik.pola_bot'), $ua);
    }

    private function ipTersamar(?string $ip): string
    {
        if (! $ip) {
            return '0';
        }
        $biner = @inet_pton($ip);
        if ($biner === false) {
            return '0';
        }
        if (strlen($biner) === 4) {
            $biner[3] = "\0";
        } else {
            $biner = substr($biner, 0, 6).str_repeat("\0", 10);
        }

        return (string) inet_ntop($biner);
    }
}
