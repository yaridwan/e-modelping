<?php

namespace App\Http\Controllers\Publik;

use App\Http\Resources\AspekPublikResource;
use App\Http\Resources\FasePublikResource;
use App\Http\Resources\ProfilPenyusunResource;
use App\Layanan\LayananSeo;
use App\Models\AspekKarakter;
use App\Models\FasePing;
use App\Models\HalamanInformasi;
use App\Models\ReferensiPustaka;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TentangController extends PublikController
{
    public function index(Request $request): Response
    {
        $halaman = HalamanInformasi::query()->terbit()->where('kode_halaman', 'tentang')->first();
        abort_unless($halaman, 404);
        $this->tandai($request, 'halaman', null, $halaman->id);
        $pemilik = $this->pemilikPublik();

        return Inertia::render('publik/tentang', [
            'halaman' => ['judul' => $halaman->judul, 'ringkasan' => $halaman->ringkasan, 'isi' => $halaman->isi, 'diperbarui_pada' => $halaman->diperbarui_pada?->toIso8601String()],
            'fase' => FasePing::query()->terbit()->with('langkah:id,fase_id,judul,urutan')->orderBy('urutan')->get()->map(fn ($f) => (new FasePublikResource($f))->resolve($request))->all(),
            'aspek' => AspekKarakter::query()->aktif()->orderBy('urutan')->get()->map(fn ($a) => (new AspekPublikResource($a))->resolve($request))->all(),
            'profil' => $pemilik ? (new ProfilPenyusunResource($pemilik))->resolve($request) : null,
            'referensi' => ReferensiPustaka::query()->where('aktif', true)->orderBy('urutan')->orderBy('penulis')->get()->map(fn ($r) => [
                'id' => $r->id, 'penulis' => $r->penulis, 'tahun' => $r->tahun, 'judul' => $r->judul, 'penerbit' => $r->nama_penerbit_jurnal,
                'volume' => $r->volume, 'nomor' => $r->nomor_terbitan, 'halaman' => $r->halaman, 'doi' => $r->doi, 'url' => $r->url, 'jenis' => $r->jenis_sumber,
            ])->all(),
            'seo' => $this->seo->bangun([
                'judul' => $halaman->judul_seo ?: $halaman->judul,
                'deskripsi' => $halaman->deskripsi_seo ?: $halaman->ringkasan,
                'jalur' => '/tentang',
                'jsonLd' => array_values(array_filter([
                    LayananSeo::jsonLdBreadcrumb([['nama' => 'Beranda', 'url' => '/'], ['nama' => $halaman->judul, 'url' => '/tentang']]),
                    $pemilik ? array_filter(['@context' => 'https://schema.org', '@type' => 'Person', 'name' => $pemilik->nama_lengkap, 'jobTitle' => $pemilik->jabatan, 'affiliation' => $pemilik->institusi ? ['@type' => 'Organization', 'name' => $pemilik->institusi] : null, 'url' => LayananSeo::absolut('/tentang')]) : null,
                ])),
            ]),
        ]);
    }
}
