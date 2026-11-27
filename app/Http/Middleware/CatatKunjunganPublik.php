<?php

namespace App\Http\Middleware;

use App\Layanan\LayananStatistik;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencatat kunjungan setelah respons terkirim, hanya bila controller menandai halaman
 * (atribut `statistik`) dan respons berstatus 200. Partial reload Inertia tidak dihitung.
 */
class CatatKunjunganPublik
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $tanda = $request->attributes->get('statistik');
        if (! is_array($tanda) || $response->getStatusCode() !== 200 || $request->method() !== 'GET') {
            return;
        }
        if ($request->header('X-Inertia-Partial-Data') || $request->header('X-Inertia-Partial-Component')) {
            return;
        }

        try {
            app(LayananStatistik::class)->catatKunjungan($request, (string) ($tanda['jenis'] ?? 'lainnya'), $tanda['bab_id'] ?? null, $tanda['halaman_id'] ?? null);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
