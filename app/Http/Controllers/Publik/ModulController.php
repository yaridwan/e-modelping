<?php

namespace App\Http\Controllers\Publik;

use App\Http\Resources\AspekPublikResource;
use App\Http\Resources\BabRingkasResource;
use App\Http\Resources\FasePublikResource;
use App\Http\Resources\MediaPublikResource;
use App\Http\Resources\VersiDokumenPublikResource;
use App\Layanan\LayananSeo;
use App\Layanan\LayananWaktuBaca;
use App\Models\AspekKarakter;
use App\Models\BabModul;
use App\Models\VersiDokumen;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModulController extends PublikController
{
    public function index(Request $request): Response
    {
        $this->tandai($request, 'daftar_bab');
        $bab = $this->pengaturan->ingat('daftar-bab', fn () => BabModul::query()->terbit()
            ->with(['subbabTerbit:id,bab_id,judul,slug,isi,contoh_praktik', 'versiAktif', 'gambarSampul'])
            ->orderBy('urutan')->get()->map(fn ($b) => (new BabRingkasResource($b))->resolve($request) + [
                'subbab' => $b->subbabTerbit->map(fn ($s) => ['judul' => $s->judul, 'slug' => $s->slug])->all(),
            ])->all());
        $modulLengkap = VersiDokumen::query()->tersediaPublik()->with('berkas')->where('jenis_dokumen', 'modul_lengkap')->first();

        return Inertia::render('publik/modul/index', [
            'bab' => $bab,
            'modul_lengkap' => $modulLengkap ? (new VersiDokumenPublikResource($modulLengkap))->resolve($request) : null,
            'seo' => $this->seo->bangun([
                'judul' => 'Daftar 9 Bab Modul',
                'deskripsi' => 'Sembilan bab E-Model Pembiasaan PING: pendahuluan, landasan teori, konsep, sintaks, peran guru, indikator karakter, instrumen, contoh implementasi, dan evaluasi.',
                'jalur' => '/modul',
                'jsonLd' => [LayananSeo::jsonLdBreadcrumb([['nama' => 'Beranda', 'url' => '/'], ['nama' => 'Modul', 'url' => '/modul']])],
            ]),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $bab = BabModul::query()->terbit()->where('slug', $slug)
            ->with(['subbabTerbit.media', 'versiAktif.berkas', 'gambarSampul', 'gambarOg', 'fase' => fn ($q) => $q->terbit()->with(['langkah.media', 'media', 'bab'])])
            ->firstOrFail();
        $this->tandai($request, 'bab', $bab->id);

        $urutan = BabModul::query()->terbit()->orderBy('urutan')->get(['id', 'nomor_romawi', 'judul', 'slug', 'urutan']);
        $indeks = $urutan->search(fn ($b) => $b->id === $bab->id);
        $navigasi = fn ($b) => $b ? ['romawi' => $b->nomor_romawi, 'judul' => $b->judul, 'url' => '/modul/'.$b->slug] : null;

        $kata = LayananWaktuBaca::jumlahKata((string) $bab->pengantar, ...$bab->subbabTerbit->map(fn ($s) => $s->isi.' '.$s->contoh_praktik)->all());
        $diperbarui = collect([$bab->diperbarui_pada, ...$bab->subbabTerbit->pluck('diperbarui_pada')->all()])->filter()->max();
        $aspek = $bab->nomor_bab === 6 || $bab->nomor_bab === 7
            ? AspekKarakter::query()->aktif()->with('indikator')->orderBy('urutan')->get()->map(fn ($a) => (new AspekPublikResource($a))->resolve($request))->all()
            : [];
        $jalur = '/modul/'.$bab->slug;

        return Inertia::render('publik/modul/show', [
            'bab' => [
                'id' => $bab->id,
                'nomor' => $bab->nomor_bab,
                'romawi' => $bab->nomor_romawi,
                'judul' => $bab->judul,
                'slug' => $bab->slug,
                'ringkasan' => $bab->ringkasan,
                'pengantar' => $bab->pengantar,
                'tujuan_pembelajaran' => $bab->tujuan_pembelajaran ?? [],
                'poin_penting' => $bab->poin_penting ?? [],
                'sampul' => $bab->gambarSampul ? (new MediaPublikResource($bab->gambarSampul))->resolve($request) : null,
                'waktu_baca_menit' => LayananWaktuBaca::menit($kata),
                'jumlah_kata' => $kata,
                'diperbarui_pada' => $diperbarui?->toIso8601String(),
                'subbab' => $bab->subbabTerbit->map(fn ($s) => [
                    'id' => $s->id,
                    'kode' => $s->kode_subbab,
                    'judul' => $s->judul,
                    'slug' => $s->slug,
                    'ringkasan' => $s->ringkasan,
                    'isi' => $s->isi,
                    'contoh_praktik' => $s->contoh_praktik,
                    'media' => $s->media ? (new MediaPublikResource($s->media))->resolve($request) : null,
                ])->all(),
                'fase' => $bab->fase->map(fn ($f) => (new FasePublikResource($f))->lengkap()->resolve($request))->all(),
            ],
            'aspek' => $aspek,
            'dokumen' => $bab->versiAktif ? (new VersiDokumenPublikResource($bab->versiAktif))->resolve($request) : null,
            'sebelumnya' => $indeks !== false && $indeks > 0 ? $navigasi($urutan[$indeks - 1]) : null,
            'berikutnya' => $indeks !== false && $indeks < $urutan->count() - 1 ? $navigasi($urutan[$indeks + 1]) : null,
            'seo' => $this->seo->bangun([
                'judul' => $bab->judul_seo ?: "Bab {$bab->nomor_romawi}: {$bab->judul}",
                'deskripsi' => $bab->deskripsi_seo ?: $bab->ringkasan,
                'jalur' => $jalur,
                'gambar' => $bab->gambarOg ?? $bab->gambarSampul,
                'jenis' => 'article',
                'jsonLd' => [
                    LayananSeo::jsonLdBreadcrumb([['nama' => 'Beranda', 'url' => '/'], ['nama' => 'Modul', 'url' => '/modul'], ['nama' => "Bab {$bab->nomor_romawi}", 'url' => $jalur]]),
                    array_filter([
                        '@context' => 'https://schema.org',
                        '@type' => 'LearningResource',
                        'name' => "Bab {$bab->nomor_romawi}: {$bab->judul}",
                        'description' => $bab->ringkasan,
                        'url' => LayananSeo::absolut($jalur),
                        'inLanguage' => 'id-ID',
                        'learningResourceType' => 'Modul panduan guru',
                        'educationalLevel' => 'Pendidikan Anak Usia Dini',
                        'audience' => ['@type' => 'EducationalAudience', 'educationalRole' => 'teacher'],
                        'isPartOf' => ['@type' => 'Book', 'name' => $this->pengaturan->ambil()->nama_aplikasi, 'url' => LayananSeo::absolut('/modul')],
                        'dateModified' => $diperbarui?->toIso8601String(),
                        'datePublished' => $bab->diterbitkan_pada?->toIso8601String(),
                        'teaches' => $bab->tujuan_pembelajaran ?: null,
                        'author' => $this->seo->penerbit($this->pemilikPublik()),
                    ]),
                ],
            ]),
        ]);
    }
}
