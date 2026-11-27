<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dan Content-Security-Policy yang kompatibel dengan SSR Inertia, PDF.js (worker),
 * grafik, dan embed video YouTube-nocookie/Vimeo. Skrip inline memakai nonce.
 */
class HeaderKeamanan
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();
        $request->attributes->set('kode_permintaan', (string) Str::uuid());

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->is('admin', 'admin/*')) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow');
            if (! $headers->has('Cache-Control') || ! str_contains((string) $headers->get('Cache-Control'), 'no-store')) {
                $headers->set('Cache-Control', 'private, no-store, max-age=0');
            }
        }

        $dev = Vite::isRunningHot() ? $this->asalDev() : '';
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'".($dev ? " {$dev}" : ''),
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net".($dev ? " {$dev}" : ''),
            "img-src 'self' data: blob:".($dev ? " {$dev}" : ''),
            "font-src 'self' data: https://fonts.bunny.net".($dev ? " {$dev}" : ''),
            "connect-src 'self'".($dev ? ' '.$dev.' '.str_replace('http', 'ws', $dev) : ''),
            "worker-src 'self' blob:",
            'frame-src https://www.youtube-nocookie.com https://player.vimeo.com',
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    private function asalDev(): string
    {
        $berkas = public_path('hot');
        $url = is_file($berkas) ? trim((string) file_get_contents($berkas)) : '';

        // Catatan: sumber CSP tidak boleh berupa literal IPv6 (mis. [::1]); karena itu Vite
        // dikunci ke 127.0.0.1 di vite.config.ts agar URL di berkas hot selalu sah.
        return $url !== '' ? rtrim($url, '/') : '';
    }
}
