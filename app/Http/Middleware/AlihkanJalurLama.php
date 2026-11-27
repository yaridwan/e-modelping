<?php

namespace App\Http\Middleware;

use App\Layanan\LayananPengaturan;
use App\Models\PengalihanUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Terapkan pengalihan URL (mis. slug lama -> slug baru) untuk permintaan GET/HEAD publik. */
class AlihkanJalurLama
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true) || $request->is('admin', 'admin/*')) {
            return $next($request);
        }

        $peta = app(LayananPengaturan::class)->ingat('pengalihan', fn () => PengalihanUrl::query()
            ->where('aktif', true)->get(['jalur_lama', 'jalur_baru', 'kode_status'])
            ->mapWithKeys(fn ($p) => [$p->jalur_lama => [$p->jalur_baru, $p->kode_status]])->all());

        $jalur = '/'.trim($request->path(), '/');
        if (isset($peta[$jalur])) {
            [$tujuan, $kode] = $peta[$jalur];
            $query = $request->getQueryString();

            return redirect()->to($tujuan.($query ? '?'.$query : ''), (int) $kode);
        }

        return $next($request);
    }
}
