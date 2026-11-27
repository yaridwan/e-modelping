<?php

namespace App\Http\Controllers\Publik;

use App\Http\Resources\MediaPublikResource;
use App\Layanan\LayananSeo;
use App\Models\GaleriKegiatan;
use App\Models\HalamanInformasi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GaleriController extends PublikController
{
    public function index(Request $request): Response
    {
        $this->tandai($request, 'galeri');
        $pengantar = HalamanInformasi::query()->terbit()->where('kode_halaman', 'galeri')->first();
        $galeri = GaleriKegiatan::query()->terbit()->with(['media', 'fase:id,nama,slug', 'bab:id,nomor_romawi,judul,slug,status'])
            ->orderBy('urutan')->orderByDesc('tanggal_kegiatan')->paginate(12)->withQueryString();

        return Inertia::render('publik/galeri', [
            'pengantar' => $pengantar ? ['judul' => $pengantar->judul, 'ringkasan' => $pengantar->ringkasan, 'isi' => $pengantar->isi] : null,
            'galeri' => $galeri->through(fn (GaleriKegiatan $g) => [
                'id' => $g->id,
                'judul' => $g->judul,
                'ringkasan' => $g->ringkasan,
                'keterangan' => $g->keterangan,
                'tanggal' => $g->tanggal_kegiatan?->toDateString(),
                'lokasi' => $g->lokasi_umum,
                'alt' => $g->teks_alternatif,
                'media' => $g->media ? (new MediaPublikResource($g->media))->resolve($request) : null,
                'fase' => $g->fase ? ['nama' => $g->fase->nama, 'url' => '/fase/'.$g->fase->slug] : null,
                'bab' => $g->bab && $g->bab->terbit() ? ['judul' => 'Bab '.$g->bab->nomor_romawi.' '.$g->bab->judul, 'url' => '/modul/'.$g->bab->slug] : null,
            ]),
            'seo' => $this->seo->bangun([
                'judul' => $pengantar?->judul ?: 'Galeri',
                'deskripsi' => $pengantar?->ringkasan ?: 'Galeri media pendukung Model Pembiasaan PING yang telah diterbitkan.',
                'jalur' => '/galeri',
                'jsonLd' => [LayananSeo::jsonLdBreadcrumb([['nama' => 'Beranda', 'url' => '/'], ['nama' => 'Galeri', 'url' => '/galeri']])],
            ]),
        ]);
    }
}
