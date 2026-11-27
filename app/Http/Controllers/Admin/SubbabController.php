<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusKonten;
use App\Http\Requests\Admin\SimpanSubbabRequest;
use App\Layanan\LayananPengaturan;
use App\Layanan\LayananPublikasi;
use App\Layanan\LayananSanitasiHtml;
use App\Layanan\LayananUrutan;
use App\Models\BabModul;
use App\Models\SubbabModul;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubbabController extends AdminController
{
    public function create(BabModul $bab): Response
    {
        return Inertia::render('admin/bab/subbab-form', [
            'bab' => $bab->only(['id', 'nomor_romawi', 'judul']),
            'subbab' => null,
            'kode_saran' => $bab->nomor_romawi.'-'.chr(65 + min(25, $bab->subbab()->count())),
        ]);
    }

    public function store(SimpanSubbabRequest $request, BabModul $bab, LayananSanitasiHtml $sanitasi): RedirectResponse
    {
        $data = $request->validated();
        $subbab = DB::transaction(function () use ($bab, $data, $sanitasi) {
            $urutan = (int) SubbabModul::query()->where('bab_id', $bab->id)->lockForUpdate()->max('urutan') + 1;
            $s = new SubbabModul;
            $s->fill([...$data, 'isi' => (string) $sanitasi->bersihkan($data['isi']), 'contoh_praktik' => $sanitasi->bersihkan($data['contoh_praktik'] ?? null)]);
            $s->forceFill(['bab_id' => $bab->id, 'urutan' => $urutan, 'dibuat_oleh' => auth()->id(), 'diperbarui_oleh' => auth()->id()])->save();
            $this->audit->catat('buat', 'subbab_modul', $s->id, "Buat subbab {$s->judul} (Bab {$bab->nomor_romawi})");

            return $s;
        });

        return $this->sukses('Subbab berhasil dibuat.', "/admin/bab/{$bab->id}/subbab/{$subbab->id}/edit");
    }

    public function edit(BabModul $bab, SubbabModul $subbab): Response
    {
        abort_unless($subbab->bab_id === $bab->id, 404);
        $subbab->load('media');

        return Inertia::render('admin/bab/subbab-form', [
            'bab' => $bab->only(['id', 'nomor_romawi', 'judul']),
            'subbab' => [
                ...$subbab->only(['id', 'kode_subbab', 'judul', 'slug', 'ringkasan', 'isi', 'contoh_praktik', 'media_id', 'metadata_sumber']),
                'status' => $subbab->status->value,
                'media' => $subbab->media ? ['id' => $subbab->media->id, 'url' => $subbab->media->urlAdmin(), 'judul' => $subbab->media->judul, 'jenis' => $subbab->media->jenis_media->value] : null,
                'diperbarui_pada' => $subbab->diperbarui_pada?->toIso8601String(),
            ],
            'kode_saran' => $subbab->kode_subbab,
        ]);
    }

    public function update(SimpanSubbabRequest $request, BabModul $bab, SubbabModul $subbab, LayananSanitasiHtml $sanitasi): RedirectResponse
    {
        abort_unless($subbab->bab_id === $bab->id, 404);
        $data = $request->validated();
        DB::transaction(function () use ($subbab, $data, $sanitasi) {
            $subbab->fill([...$data, 'isi' => (string) $sanitasi->bersihkan($data['isi']), 'contoh_praktik' => $sanitasi->bersihkan($data['contoh_praktik'] ?? null)]);
            $subbab->forceFill(['diperbarui_oleh' => auth()->id()])->save();
            $this->audit->catatModel('perbarui', $subbab, "Perbarui subbab {$subbab->judul}");
        });

        return $this->sukses('Subbab berhasil disimpan.');
    }

    public function status(Request $request, BabModul $bab, SubbabModul $subbab, LayananPublikasi $publikasi): RedirectResponse
    {
        abort_unless($subbab->bab_id === $bab->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in(StatusKonten::nilai())]]);
        $publikasi->ubahStatus($subbab, StatusKonten::from($data['status']), "Subbab {$subbab->judul}");

        return $this->sukses('Status subbab diubah menjadi '.StatusKonten::from($data['status'])->label().'.');
    }

    public function urutan(Request $request, BabModul $bab, LayananUrutan $urutan): RedirectResponse
    {
        $data = $request->validate(['id' => ['required', 'array', 'min:1'], 'id.*' => ['integer']]);
        $urutan->terapkan('subbab_modul', $data['id'], ['bab_id' => $bab->id]);
        $this->audit->catat('urutkan', 'bab_modul', $bab->id, "Urutkan ulang subbab Bab {$bab->nomor_romawi}", ['urutan_id' => $data['id']]);
        app(LayananPengaturan::class)->naikkanVersiKonten();

        return $this->sukses('Urutan subbab disimpan.');
    }

    /** Subbab boleh dihapus, tetapi bab minimal menyisakan satu subbab (struktur inti dijaga). */
    public function destroy(BabModul $bab, SubbabModul $subbab): RedirectResponse
    {
        abort_unless($subbab->bab_id === $bab->id, 404);
        if ($bab->subbab()->count() <= 1) {
            return back()->with('galat', 'Bab harus memiliki minimal satu subbab. Ubah status menjadi draf/arsip sebagai gantinya.');
        }
        DB::transaction(function () use ($bab, $subbab) {
            $this->audit->catat('hapus', 'subbab_modul', $subbab->id, "Hapus subbab {$subbab->judul} (Bab {$bab->nomor_romawi})", ['judul' => $subbab->judul, 'kode_subbab' => $subbab->kode_subbab]);
            $subbab->delete();
            // Rapatkan urutan agar tetap berurutan 1..n.
            $sisa = SubbabModul::query()->where('bab_id', $bab->id)->orderBy('urutan')->pluck('id')->all();
            app(LayananUrutan::class)->terapkan('subbab_modul', $sisa, ['bab_id' => $bab->id]);
        });

        return $this->sukses('Subbab dihapus.', "/admin/bab/{$bab->id}/edit");
    }
}
