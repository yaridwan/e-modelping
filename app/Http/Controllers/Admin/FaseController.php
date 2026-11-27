<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SimpanFaseRequest;
use App\Models\FasePing;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Tiga fase PING bersifat inti: dapat disunting, tidak dapat dibuat/dihapus. */
class FaseController extends AdminController
{
    public function index(): Response
    {
        return Inertia::render('admin/fase/index', [
            'fase' => FasePing::query()->withCount('langkah')->orderBy('urutan')->get()->map(fn ($f) => [
                'id' => $f->id, 'kode' => $f->kode_fase, 'nama' => $f->nama, 'judul_indonesia' => $f->judul_indonesia,
                'durasi' => $f->teksDurasi(), 'langkah' => $f->langkah_count, 'status' => $f->status->value,
                'diperbarui_pada' => $f->diperbarui_pada?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function edit(FasePing $fase): Response
    {
        $fase->load(['langkah.media', 'media']);

        return Inertia::render('admin/fase/edit', [
            'fase' => [
                ...$fase->only(['id', 'kode_fase', 'nama', 'slug', 'judul_indonesia', 'deskripsi', 'waktu_pelaksanaan', 'durasi_minimum_menit', 'durasi_maksimum_menit', 'frekuensi_panduan', 'catatan_guru', 'media_id']),
                'status' => $fase->status->value,
                'tujuan' => $fase->tujuan ?? [],
                'indikator_keberhasilan' => $fase->indikator_keberhasilan ?? [],
                'penguatan_karakter' => $fase->penguatan_karakter ?? [],
                'media' => $fase->media ? ['id' => $fase->media->id, 'url' => $fase->media->urlAdmin(), 'judul' => $fase->media->judul, 'jenis' => $fase->media->jenis_media->value] : null,
            ],
            'langkah' => $fase->langkah->map(fn ($l) => [
                ...$l->only(['id', 'nomor_langkah', 'judul', 'penjelasan', 'aktivitas_guru', 'aktivitas_anak', 'media_id', 'urutan']),
                'contoh_dialog' => $l->contoh_dialog ?? [],
                'media' => $l->media ? ['id' => $l->media->id, 'url' => $l->media->urlAdmin(), 'judul' => $l->media->judul, 'jenis' => $l->media->jenis_media->value] : null,
            ])->all(),
        ]);
    }

    public function update(SimpanFaseRequest $request, FasePing $fase): RedirectResponse
    {
        $data = $request->validated();
        $fase->fill([
            ...$data,
            'tujuan' => array_values(array_filter($data['tujuan'] ?? [])),
            'indikator_keberhasilan' => array_values(array_filter($data['indikator_keberhasilan'] ?? [])),
            'penguatan_karakter' => array_values($data['penguatan_karakter'] ?? []),
        ])->save();
        $this->audit->catatModel('perbarui', $fase, "Perbarui fase {$fase->nama}");

        return $this->sukses('Fase berhasil disimpan.');
    }
}
