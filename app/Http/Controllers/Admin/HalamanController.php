<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusKonten;
use App\Http\Requests\Admin\SimpanHalamanRequest;
use App\Layanan\LayananPublikasi;
use App\Layanan\LayananSanitasiHtml;
use App\Models\HalamanInformasi;
use App\Models\MenuNavigasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HalamanController extends AdminController
{
    public function index(Request $request): Response
    {
        $p = $this->parameterDaftar($request, ['judul', 'status', 'diperbarui_pada'], 'judul');
        $status = in_array($request->query('status'), StatusKonten::nilai(), true) ? $request->query('status') : null;

        return Inertia::render('admin/halaman/index', [
            'halaman' => HalamanInformasi::query()
                ->when($p['q'] !== '', fn ($q) => $q->where('judul', 'like', self::pola($p['q'])))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByDesc('halaman_inti')->orderBy($p['urut'], $p['arah'])
                ->paginate($p['per'])->withQueryString()
                ->through(fn ($h) => ['id' => $h->id, 'kode' => $h->kode_halaman, 'judul' => $h->judul, 'slug' => $h->slug, 'status' => $h->status->value, 'inti' => $h->halaman_inti, 'url' => $this->urlPublik($h), 'diperbarui_pada' => $h->diperbarui_pada?->toIso8601String()]),
            'filter' => $p + ['status' => $status],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/halaman/form', ['halaman' => null]);
    }

    public function store(SimpanHalamanRequest $request, LayananSanitasiHtml $sanitasi): RedirectResponse
    {
        $data = $request->validated();
        $h = DB::transaction(function () use ($data, $sanitasi) {
            $h = new HalamanInformasi;
            $h->fill([...$data, 'isi' => $sanitasi->bersihkan($data['isi'] ?? null)]);
            $h->forceFill([
                'kode_halaman' => 'tambahan-'.Str::lower(Str::random(8)),
                'halaman_inti' => false,
                'diterbitkan_pada' => $data['status'] === 'terbit' ? now() : null,
                'dibuat_oleh' => auth()->id(), 'diperbarui_oleh' => auth()->id(),
            ])->save();
            $this->audit->catat('buat', 'halaman_informasi', $h->id, "Buat halaman {$h->judul}");

            return $h;
        });

        return $this->sukses('Halaman dibuat.', "/admin/halaman/{$h->id}/edit");
    }

    public function edit(HalamanInformasi $halaman): Response
    {
        $halaman->load(['gambarSampul', 'gambarOg']);

        return Inertia::render('admin/halaman/form', [
            'halaman' => [
                ...$halaman->only(['id', 'kode_halaman', 'judul', 'slug', 'ringkasan', 'isi', 'judul_seo', 'deskripsi_seo', 'gambar_sampul_id', 'gambar_og_id', 'tampilkan_di_navigasi', 'halaman_inti']),
                'status' => $halaman->status->value,
                'url' => $this->urlPublik($halaman),
                'gambar_sampul' => $halaman->gambarSampul ? ['id' => $halaman->gambarSampul->id, 'url' => $halaman->gambarSampul->urlAdmin(), 'judul' => $halaman->gambarSampul->judul] : null,
                'gambar_og' => $halaman->gambarOg ? ['id' => $halaman->gambarOg->id, 'url' => $halaman->gambarOg->urlAdmin(), 'judul' => $halaman->gambarOg->judul] : null,
            ],
        ]);
    }

    public function update(SimpanHalamanRequest $request, HalamanInformasi $halaman, LayananSanitasiHtml $sanitasi, LayananPublikasi $publikasi): RedirectResponse
    {
        $data = $request->validated();
        $slugLama = $halaman->slug;
        DB::transaction(function () use ($halaman, $data, $sanitasi, $publikasi, $slugLama) {
            $halaman->fill([...$data, 'isi' => $sanitasi->bersihkan($data['isi'] ?? null)]);
            if ($data['status'] === 'terbit' && ! $halaman->diterbitkan_pada) {
                $halaman->forceFill(['diterbitkan_pada' => now()]);
            }
            $halaman->forceFill(['diperbarui_oleh' => auth()->id()])->save();
            $this->audit->catatModel('perbarui', $halaman, "Perbarui halaman {$halaman->judul}");
            if (! $halaman->halaman_inti && $slugLama !== $halaman->slug) {
                $publikasi->catatPengalihan('/'.$slugLama, '/'.$halaman->slug);
            }
        });

        return $this->sukses('Halaman disimpan.');
    }

    public function status(Request $request, HalamanInformasi $halaman, LayananPublikasi $publikasi): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(StatusKonten::nilai())]]);
        $publikasi->ubahStatus($halaman, StatusKonten::from($data['status']), "Halaman {$halaman->judul}");

        return $this->sukses('Status halaman diubah.');
    }

    public function destroy(HalamanInformasi $halaman): RedirectResponse
    {
        if ($halaman->halaman_inti) {
            return back()->with('galat', 'Halaman inti tidak dapat dihapus. Ubah statusnya menjadi draf bila ingin disembunyikan.');
        }
        DB::transaction(function () use ($halaman) {
            $this->audit->catat('hapus', 'halaman_informasi', $halaman->id, "Hapus halaman {$halaman->judul}", ['judul' => $halaman->judul, 'slug' => $halaman->slug]);
            MenuNavigasi::query()->where('halaman_id', $halaman->id)->delete();
            $halaman->delete();
        });

        return $this->sukses('Halaman dihapus beserta tautan menu yang mengarah ke halaman tersebut.', '/admin/halaman');
    }

    private function urlPublik(HalamanInformasi $h): string
    {
        return match ($h->kode_halaman) {
            'tentang' => '/tentang', 'panduan-penggunaan' => '/panduan-penggunaan', 'kebijakan-privasi' => '/kebijakan-privasi',
            'kontak' => '/kontak', 'pertanyaan-umum' => '/pertanyaan-umum', 'galeri' => '/galeri',
            default => '/'.$h->slug,
        };
    }
}
