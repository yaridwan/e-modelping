<?php

namespace App\Http\Controllers\Publik;

use App\Http\Resources\MediaPublikResource;
use App\Layanan\LayananSeo;
use App\Models\HalamanInformasi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HalamanController extends PublikController
{
    public function panduan(Request $request): Response
    {
        return $this->tampilkan($request, HalamanInformasi::query()->terbit()->where('kode_halaman', 'panduan-penggunaan')->firstOrFail(), '/panduan-penggunaan');
    }

    public function privasi(Request $request): Response
    {
        return $this->tampilkan($request, HalamanInformasi::query()->terbit()->where('kode_halaman', 'kebijakan-privasi')->firstOrFail(), '/kebijakan-privasi');
    }

    public function show(Request $request, string $slug): Response
    {
        // Halaman inti memiliki URL tetap sendiri; slug bebas hanya untuk halaman tambahan.
        $halaman = HalamanInformasi::query()->terbit()->where('slug', $slug)
            ->whereNotIn('kode_halaman', ['tentang', 'panduan-penggunaan', 'kebijakan-privasi', 'kontak', 'pertanyaan-umum', 'galeri'])
            ->firstOrFail();

        return $this->tampilkan($request, $halaman, '/'.$halaman->slug);
    }

    private function tampilkan(Request $request, HalamanInformasi $halaman, string $jalur): Response
    {
        $halaman->loadMissing('gambarSampul', 'gambarOg');
        $this->tandai($request, 'halaman', null, $halaman->id);

        return Inertia::render('publik/halaman', [
            'halaman' => [
                'kode' => $halaman->kode_halaman,
                'judul' => $halaman->judul,
                'ringkasan' => $halaman->ringkasan,
                'isi' => $halaman->isi,
                'sampul' => $halaman->gambarSampul ? (new MediaPublikResource($halaman->gambarSampul))->resolve($request) : null,
                'diperbarui_pada' => $halaman->diperbarui_pada?->toIso8601String(),
            ],
            'seo' => $this->seo->bangun([
                'judul' => $halaman->judul_seo ?: $halaman->judul,
                'deskripsi' => $halaman->deskripsi_seo ?: $halaman->ringkasan,
                'jalur' => $jalur,
                'gambar' => $halaman->gambarOg ?? $halaman->gambarSampul,
                'jsonLd' => [LayananSeo::jsonLdBreadcrumb([['nama' => 'Beranda', 'url' => '/'], ['nama' => $halaman->judul, 'url' => $jalur]])],
            ]),
        ]);
    }
}
