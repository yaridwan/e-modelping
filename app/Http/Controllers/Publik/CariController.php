<?php

namespace App\Http\Controllers\Publik;

use App\Layanan\LayananPencarian;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CariController extends PublikController
{
    public function index(Request $request, LayananPencarian $pencarian): Response
    {
        $q = Str::limit(trim((string) $request->query('q', '')), 80, '');
        $this->tandai($request, 'cari');

        return Inertia::render('publik/cari', [
            'q' => $q,
            'hasil' => $q !== '' ? $pencarian->cari($q) : [],
            'seo' => $this->seo->bangun([
                'judul' => $q !== '' ? "Hasil pencarian “{$q}”" : 'Cari Materi',
                'deskripsi' => 'Pencarian materi HTML E-Model PING yang telah diterbitkan.',
                'jalur' => '/cari',
                'robots' => 'noindex,follow',
            ]),
        ]);
    }
}
