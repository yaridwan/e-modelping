<?php

namespace App\Http\Controllers\Publik;

use App\Http\Resources\FasePublikResource;
use App\Layanan\LayananSeo;
use App\Models\FasePing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaseController extends PublikController
{
    public function show(Request $request, string $slug): Response
    {
        $fase = FasePing::query()->terbit()->where('slug', $slug)->with(['langkah.media', 'media', 'bab'])->firstOrFail();
        $this->tandai($request, 'fase');
        $semua = FasePing::query()->terbit()->orderBy('urutan')->get(['id', 'nama', 'slug', 'kode_fase', 'judul_indonesia']);
        $jalur = '/fase/'.$fase->slug;

        return Inertia::render('publik/fase/show', [
            'fase' => (new FasePublikResource($fase))->lengkap()->resolve($request),
            'semua_fase' => $semua->map(fn ($f) => ['nama' => $f->nama, 'kode' => $f->kode_fase, 'judul_indonesia' => $f->judul_indonesia, 'url' => '/fase/'.$f->slug, 'aktif' => $f->id === $fase->id])->all(),
            'seo' => $this->seo->bangun([
                'judul' => "{$fase->nama}: {$fase->judul_indonesia}",
                'deskripsi' => $fase->deskripsi,
                'jalur' => $jalur,
                'jenis' => 'article',
                'jsonLd' => [
                    LayananSeo::jsonLdBreadcrumb([['nama' => 'Beranda', 'url' => '/'], ['nama' => 'Fase PING', 'url' => '/tentang#fase'], ['nama' => $fase->nama, 'url' => $jalur]]),
                    ['@context' => 'https://schema.org', '@type' => 'LearningResource', 'name' => $fase->nama, 'description' => $fase->deskripsi, 'url' => LayananSeo::absolut($jalur), 'inLanguage' => 'id-ID', 'timeRequired' => $fase->durasi_maksimum_menit ? 'PT'.$fase->durasi_maksimum_menit.'M' : null],
                ],
            ]),
        ]);
    }
}
