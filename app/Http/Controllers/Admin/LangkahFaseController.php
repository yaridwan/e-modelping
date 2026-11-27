<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SimpanLangkahRequest;
use App\Layanan\LayananPengaturan;
use App\Layanan\LayananUrutan;
use App\Models\FasePing;
use App\Models\LangkahFase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LangkahFaseController extends AdminController
{
    public function store(SimpanLangkahRequest $request, FasePing $fase): RedirectResponse
    {
        DB::transaction(function () use ($request, $fase) {
            $maks = (int) LangkahFase::query()->where('fase_id', $fase->id)->lockForUpdate()->max('urutan');
            $l = new LangkahFase;
            $l->fill([...$request->validated(), 'nomor_langkah' => $maks + 1, 'urutan' => $maks + 1]);
            $l->forceFill(['fase_id' => $fase->id])->save();
            $this->audit->catat('buat', 'langkah_fase', $l->id, "Tambah langkah {$l->judul} pada {$fase->nama}");
        });

        return $this->sukses('Langkah ditambahkan.');
    }

    public function update(SimpanLangkahRequest $request, FasePing $fase, LangkahFase $langkah): RedirectResponse
    {
        abort_unless($langkah->fase_id === $fase->id, 404);
        $langkah->fill($request->validated())->save();
        $this->audit->catatModel('perbarui', $langkah, "Perbarui langkah {$langkah->judul}");

        return $this->sukses('Langkah disimpan.');
    }

    public function urutan(Request $request, FasePing $fase, LayananUrutan $urutan): RedirectResponse
    {
        $data = $request->validate(['id' => ['required', 'array', 'min:1'], 'id.*' => ['integer']]);
        DB::transaction(function () use ($urutan, $data, $fase) {
            $urutan->terapkan('langkah_fase', $data['id'], ['fase_id' => $fase->id]);
            // Nomor langkah mengikuti urutan baru.
            $urutan->terapkan('langkah_fase', $data['id'], ['fase_id' => $fase->id], 'nomor_langkah');
        });
        $this->audit->catat('urutkan', 'fase_ping', $fase->id, "Urutkan ulang langkah {$fase->nama}");
        app(LayananPengaturan::class)->naikkanVersiKonten();

        return $this->sukses('Urutan langkah disimpan.');
    }

    public function destroy(FasePing $fase, LangkahFase $langkah, LayananUrutan $urutan): RedirectResponse
    {
        abort_unless($langkah->fase_id === $fase->id, 404);
        if ($fase->langkah()->count() <= 1) {
            return back()->with('galat', 'Fase harus memiliki minimal satu langkah.');
        }
        DB::transaction(function () use ($fase, $langkah, $urutan) {
            $this->audit->catat('hapus', 'langkah_fase', $langkah->id, "Hapus langkah {$langkah->judul} pada {$fase->nama}");
            $langkah->delete();
            $sisa = LangkahFase::query()->where('fase_id', $fase->id)->orderBy('urutan')->pluck('id')->all();
            $urutan->terapkan('langkah_fase', $sisa, ['fase_id' => $fase->id]);
            $urutan->terapkan('langkah_fase', $sisa, ['fase_id' => $fase->id], 'nomor_langkah');
        });

        return $this->sukses('Langkah dihapus.');
    }
}
