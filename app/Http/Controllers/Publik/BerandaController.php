<?php

namespace App\Http\Controllers\Publik;

use App\Http\Resources\AspekPublikResource;
use App\Http\Resources\BabRingkasResource;
use App\Http\Resources\FasePublikResource;
use App\Http\Resources\MediaPublikResource;
use App\Http\Resources\ProfilPenyusunResource;
use App\Http\Resources\VersiDokumenPublikResource;
use App\Models\AspekKarakter;
use App\Models\BabModul;
use App\Models\BagianBeranda;
use App\Models\FasePing;
use App\Models\GaleriKegiatan;
use App\Models\PertanyaanUmum;
use App\Models\VersiDokumen;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BerandaController extends PublikController
{
    public function index(Request $request): Response
    {
        $this->tandai($request, 'beranda');

        $data = $this->pengaturan->ingat('beranda', function () use ($request) {
            $bagian = BagianBeranda::query()->with('media')->aktif()->orderBy('urutan')->get();
            $jenisAktif = $bagian->pluck('jenis_bagian')->all();
            $modulLengkap = VersiDokumen::query()->tersediaPublik()->with('berkas')->where('jenis_dokumen', 'modul_lengkap')->first();

            return [
                'bagian' => $bagian->map(fn (BagianBeranda $b) => [
                    'kode' => $b->kode_bagian,
                    'jenis' => $b->jenis_bagian,
                    'judul' => $b->judul,
                    'subjudul' => $b->subjudul,
                    'ringkasan' => $b->ringkasan,
                    'isi' => $b->isi,
                    'tombol_utama' => $b->teks_tombol_utama ? ['teks' => $b->teks_tombol_utama, 'url' => $b->tautan_tombol_utama] : null,
                    'tombol_pendamping' => $b->teks_tombol_pendamping ? ['teks' => $b->teks_tombol_pendamping, 'url' => $b->tautan_tombol_pendamping] : null,
                    'media' => $b->media ? (new MediaPublikResource($b->media))->resolve($request) : null,
                    'pengaturan' => $b->pengaturan_tampilan ?? [],
                ])->all(),
                'fase' => in_array('fase', $jenisAktif, true) || in_array('hero', $jenisAktif, true)
                    ? FasePing::query()->terbit()->with('langkah:id,fase_id,judul,urutan')->orderBy('urutan')->get()->map(fn ($f) => (new FasePublikResource($f))->resolve($request))->all() : [],
                'aspek' => in_array('aspek', $jenisAktif, true)
                    ? AspekKarakter::query()->aktif()->with('indikator')->orderBy('urutan')->get()->map(fn ($a) => (new AspekPublikResource($a))->resolve($request))->all() : [],
                'bab' => in_array('bab', $jenisAktif, true)
                    ? BabModul::query()->terbit()->with(['subbabTerbit:id,bab_id,isi,contoh_praktik', 'versiAktif', 'gambarSampul'])->orderBy('urutan')->get()->map(fn ($b) => (new BabRingkasResource($b))->resolve($request))->all() : [],
                'faq' => in_array('faq', $jenisAktif, true)
                    ? PertanyaanUmum::query()->aktif()->orderBy('urutan')->limit(6)->get(['id', 'pertanyaan', 'jawaban'])->toArray() : [],
                'galeri' => in_array('galeri', $jenisAktif, true)
                    ? GaleriKegiatan::query()->terbit()->with('media')->orderBy('urutan')->limit(6)->get()->map(fn ($g) => [
                        'id' => $g->id, 'judul' => $g->judul, 'ringkasan' => $g->ringkasan,
                        'media' => $g->media ? (new MediaPublikResource($g->media))->resolve($request) : null,
                        'alt' => $g->teks_alternatif,
                    ])->filter(fn ($g) => $g['media'])->values()->all() : [],
                'modul_lengkap' => $modulLengkap ? (new VersiDokumenPublikResource($modulLengkap))->resolve($request) : null,
            ];
        });

        $pemilik = $this->pemilikPublik();
        $data['profil'] = $pemilik ? (new ProfilPenyusunResource($pemilik))->resolve($request) : null;

        $faqTampil = collect($data['faq']);

        return Inertia::render('publik/beranda', $data + [
            'seo' => $this->seo->bangun([
                'jalur' => '/',
                'jsonLd' => array_values(array_filter([
                    ...$this->seo->jsonLdSitus($pemilik),
                    $faqTampil->isNotEmpty() ? [
                        '@context' => 'https://schema.org',
                        '@type' => 'FAQPage',
                        'mainEntity' => $faqTampil->map(fn ($f) => ['@type' => 'Question', 'name' => $f['pertanyaan'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['jawaban']]])->all(),
                    ] : null,
                ])),
            ]),
        ]);
    }
}
