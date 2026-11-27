<?php

namespace App\Http\Controllers\Publik;

use App\Http\Resources\AspekPublikResource;
use App\Layanan\LayananSeo;
use App\Models\AspekKarakter;
use App\Models\FasePing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KarakterController extends PublikController
{
    public function index(Request $request): Response
    {
        $this->tandai($request, 'karakter');

        return Inertia::render('publik/karakter', [
            'aspek' => AspekKarakter::query()->aktif()->with('indikator')->orderBy('urutan')->get()->map(fn ($a) => (new AspekPublikResource($a))->resolve($request))->all(),
            'fase' => FasePing::query()->terbit()->orderBy('urutan')->get(['kode_fase', 'nama', 'slug'])->map(fn ($f) => ['kode' => $f->kode_fase, 'nama' => $f->nama, 'url' => '/fase/'.$f->slug])->all(),
            'seo' => $this->seo->bangun([
                'judul' => 'Empat Aspek dan 16 Indikator Karakter',
                'deskripsi' => 'Disiplin, tanggung jawab, kontrol diri, dan sopan santun beserta 16 indikator perilaku, contoh pengamatan, dan rubrik BB–BSB sebagai materi referensi guru PAUD.',
                'jalur' => '/karakter',
                'jsonLd' => [LayananSeo::jsonLdBreadcrumb([['nama' => 'Beranda', 'url' => '/'], ['nama' => 'Aspek Karakter', 'url' => '/karakter']])],
            ]),
        ]);
    }
}
