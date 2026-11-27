<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusKonten;
use App\Http\Requests\Admin\SimpanGaleriRequest;
use App\Layanan\LayananPublikasi;
use App\Models\BabModul;
use App\Models\FasePing;
use App\Models\GaleriKegiatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GaleriController extends AdminController
{
    public function index(Request $request): Response
    {
        $p = $this->parameterDaftar($request, ['urutan', 'judul', 'tanggal_kegiatan', 'status'], 'urutan');
        $status = in_array($request->query('status'), StatusKonten::nilai(), true) ? $request->query('status') : null;

        return Inertia::render('admin/galeri/index', [
            'galeri' => GaleriKegiatan::query()->with('media')
                ->when($p['q'] !== '', fn ($q) => $q->where('judul', 'like', self::pola($p['q'])))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderBy($p['urut'], $p['arah'])->paginate($p['per'])->withQueryString()
                ->through(fn ($g) => ['id' => $g->id, 'judul' => $g->judul, 'status' => $g->status->value, 'tanggal' => $g->tanggal_kegiatan?->toDateString(), 'urutan' => $g->urutan, 'gambar' => $g->media?->urlAdmin(), 'izin' => (bool) $g->dasar_izin_publikasi]),
            'filter' => $p + ['status' => $status],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/galeri/form', ['galeri' => null, ...$this->opsi()]);
    }

    public function store(SimpanGaleriRequest $request): RedirectResponse
    {
        $g = new GaleriKegiatan;
        $g->fill($request->validated());
        $g->forceFill(['status' => StatusKonten::Draf, 'urutan' => (int) ($request->validated()['urutan'] ?? 0), 'dibuat_oleh' => auth()->id(), 'diperbarui_oleh' => auth()->id()])->save();
        $this->audit->catat('buat', 'galeri_kegiatan', $g->id, "Buat galeri {$g->judul}");

        return $this->sukses('Item galeri dibuat sebagai draf.', "/admin/galeri/{$g->id}/edit");
    }

    public function edit(GaleriKegiatan $galeri): Response
    {
        $galeri->load('media');

        return Inertia::render('admin/galeri/form', [
            'galeri' => [
                ...$galeri->only(['id', 'judul', 'slug', 'ringkasan', 'media_id', 'bab_id', 'fase_id', 'lokasi_umum', 'keterangan', 'teks_alternatif', 'dasar_izin_publikasi', 'urutan']),
                'tanggal_kegiatan' => $galeri->tanggal_kegiatan?->toDateString(),
                'status' => $galeri->status->value,
                'media' => $galeri->media ? ['id' => $galeri->media->id, 'url' => $galeri->media->urlAdmin(), 'judul' => $galeri->media->judul] : null,
            ],
            ...$this->opsi(),
        ]);
    }

    public function update(SimpanGaleriRequest $request, GaleriKegiatan $galeri): RedirectResponse
    {
        $galeri->fill($request->validated())->forceFill(['diperbarui_oleh' => auth()->id()])->save();
        $this->audit->catatModel('perbarui', $galeri, "Perbarui galeri {$galeri->judul}");

        return $this->sukses('Item galeri disimpan.');
    }

    public function status(Request $request, GaleriKegiatan $galeri, LayananPublikasi $publikasi): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(StatusKonten::nilai())]]);
        if ($data['status'] === 'terbit' && ! $galeri->dasar_izin_publikasi) {
            return back()->with('galat', 'Isi “Dasar izin publikasi” sebelum menerbitkan dokumentasi kegiatan.');
        }
        $publikasi->ubahStatus($galeri, StatusKonten::from($data['status']), "Galeri {$galeri->judul}");

        return $this->sukses('Status galeri diubah.');
    }

    public function destroy(GaleriKegiatan $galeri): RedirectResponse
    {
        $this->audit->catat('hapus', 'galeri_kegiatan', $galeri->id, "Hapus galeri {$galeri->judul}");
        $galeri->delete();

        return $this->sukses('Item galeri dihapus (berkas gambar tetap ada di pustaka media).', '/admin/galeri');
    }

    private function opsi(): array
    {
        return [
            'bab' => BabModul::query()->orderBy('urutan')->get(['id', 'nomor_romawi', 'judul'])->map(fn ($b) => ['id' => $b->id, 'label' => "Bab {$b->nomor_romawi} · {$b->judul}"])->all(),
            'fase' => FasePing::query()->orderBy('urutan')->get(['id', 'nama'])->map(fn ($f) => ['id' => $f->id, 'label' => $f->nama])->all(),
        ];
    }
}
