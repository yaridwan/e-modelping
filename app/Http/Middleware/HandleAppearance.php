<?php

namespace App\Http\Middleware;

use App\Layanan\LayananPengaturan;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menentukan tema awal sebelum render (mencegah kilatan tema & hydration mismatch).
 * Urutan: cookie `appearance` perangkat -> preferensi profil admin (rute admin) -> tema bawaan situs.
 */
class HandleAppearance
{
    public function handle(Request $request, Closure $next): Response
    {
        $peta = ['terang' => 'light', 'gelap' => 'dark', 'sistem' => 'system'];
        $cookie = $request->cookie('appearance');
        $tema = in_array($cookie, ['light', 'dark', 'system'], true) ? $cookie : null;

        if (! $tema && $request->is('admin', 'admin/*') && $request->user()) {
            $tema = $peta[$request->user()->tema_pilihan] ?? null;
        }
        if (! $tema) {
            $tema = $peta[app(LayananPengaturan::class)->ambil()->tema_bawaan ?? 'sistem'] ?? 'system';
        }

        View::share('appearance', $tema);

        return $next($request);
    }
}
