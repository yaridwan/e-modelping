<?php

namespace App\Http\Controllers\Publik;

use App\Layanan\LayananSeo;
use App\Models\HalamanInformasi;
use App\Models\PertanyaanUmum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PertanyaanUmumController extends PublikController
{
    public function index(Request $request): Response
    {
        $this->tandai($request, 'faq');
        $faq = PertanyaanUmum::query()->aktif()->orderBy('kategori')->orderBy('urutan')->get(['id', 'kategori', 'pertanyaan', 'jawaban']);
        $pengantar = HalamanInformasi::query()->terbit()->where('kode_halaman', 'pertanyaan-umum')->first();

        return Inertia::render('publik/faq', [
            'pengantar' => $pengantar ? ['judul' => $pengantar->judul, 'ringkasan' => $pengantar->ringkasan] : null,
            'kelompok' => $faq->groupBy('kategori')->map(fn ($item, $kategori) => ['kategori' => $kategori, 'item' => $item->values()->toArray()])->values()->all(),
            'seo' => $this->seo->bangun([
                'judul' => $pengantar?->judul ?: 'Pertanyaan Umum',
                'deskripsi' => $pengantar?->ringkasan ?: 'Jawaban atas pertanyaan yang sering diajukan tentang Model Pembiasaan PING dan cara memakai e-modul ini.',
                'jalur' => '/pertanyaan-umum',
                'jsonLd' => array_values(array_filter([
                    LayananSeo::jsonLdBreadcrumb([['nama' => 'Beranda', 'url' => '/'], ['nama' => 'Pertanyaan Umum', 'url' => '/pertanyaan-umum']]),
                    // FAQ schema hanya untuk tanya-jawab yang benar-benar tampil di halaman ini.
                    $faq->isNotEmpty() ? ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faq->map(fn ($f) => ['@type' => 'Question', 'name' => $f->pertanyaan, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f->jawaban]])->all()] : null,
                ])),
            ]),
        ]);
    }
}
