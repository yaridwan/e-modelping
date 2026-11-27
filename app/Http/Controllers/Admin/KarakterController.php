<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SimpanAspekRequest;
use App\Http\Requests\Admin\SimpanIndikatorRequest;
use App\Models\AspekKarakter;
use App\Models\IndikatorKarakter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Empat aspek dan 16 indikator adalah referensi materi (bukan penilaian anak daring). */
class KarakterController extends AdminController
{
    public function index(): Response
    {
        return Inertia::render('admin/karakter/index', [
            'aspek' => AspekKarakter::query()->with('indikator')->orderBy('urutan')->get()->map(fn ($a) => [
                ...$a->only(['id', 'kode_aspek', 'nama', 'slug', 'deskripsi', 'contoh_pembiasaan', 'fokus_pengamatan', 'ikon', 'warna', 'aktif']),
                'indikator' => $a->indikator->map(fn ($i) => [
                    ...$i->only(['id', 'nomor_indikator', 'rumusan', 'contoh_pengamatan', 'catatan_pendampingan', 'rubrik_bb', 'rubrik_mb', 'rubrik_bsh', 'rubrik_bsb', 'aktif']),
                    'fase_pengamatan' => $i->fase_pengamatan ?? [],
                ])->all(),
            ])->all(),
            'ikon' => SimpanAspekRequest::IKON,
        ]);
    }

    public function updateAspek(SimpanAspekRequest $request, AspekKarakter $aspek): RedirectResponse
    {
        $aspek->fill($request->validated())->save();
        $this->audit->catatModel('perbarui', $aspek, "Perbarui aspek {$aspek->nama}");

        return $this->sukses('Aspek karakter disimpan.');
    }

    public function updateIndikator(SimpanIndikatorRequest $request, IndikatorKarakter $indikator): RedirectResponse
    {
        $indikator->fill($request->validated())->save();
        $this->audit->catatModel('perbarui', $indikator, "Perbarui indikator {$indikator->nomor_indikator}");

        return $this->sukses('Indikator disimpan.');
    }
}
