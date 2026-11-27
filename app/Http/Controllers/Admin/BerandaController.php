<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SimpanBagianBerandaRequest;
use App\Layanan\LayananPengaturan;
use App\Layanan\LayananUrutan;
use App\Models\BagianBeranda;
use App\Support\SkemaBagianBeranda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BerandaController extends AdminController
{
    public function index(): Response
    {
        return Inertia::render('admin/beranda/index', [
            'bagian' => BagianBeranda::query()->with('media')->orderBy('urutan')->get()->map(fn (BagianBeranda $b) => [
                ...$b->only(['id', 'kode_bagian', 'jenis_bagian', 'judul', 'subjudul', 'ringkasan', 'isi', 'teks_tombol_utama', 'tautan_tombol_utama', 'teks_tombol_pendamping', 'tautan_tombol_pendamping', 'media_id', 'urutan', 'aktif']),
                'pengaturan_tampilan' => SkemaBagianBeranda::saring($b->jenis_bagian, $b->pengaturan_tampilan),
                'skema' => SkemaBagianBeranda::bidang($b->jenis_bagian),
                'media' => $b->media ? ['id' => $b->media->id, 'url' => $b->media->urlAdmin(), 'judul' => $b->media->judul, 'jenis' => $b->media->jenis_media->value] : null,
            ])->all(),
        ]);
    }

    public function update(SimpanBagianBerandaRequest $request, BagianBeranda $bagian): RedirectResponse
    {
        $data = $request->validated();
        $data['pengaturan_tampilan'] = SkemaBagianBeranda::saring($bagian->jenis_bagian, $data['pengaturan_tampilan'] ?? []);
        $bagian->fill($data)->forceFill(['diperbarui_oleh' => auth()->id()])->save();
        $this->audit->catatModel('perbarui', $bagian, "Perbarui bagian beranda {$bagian->judul}");

        return $this->sukses('Bagian beranda disimpan.');
    }

    public function urutan(Request $request, LayananUrutan $urutan): RedirectResponse
    {
        $data = $request->validate(['id' => ['required', 'array', 'min:1'], 'id.*' => ['integer']]);
        $urutan->terapkan('bagian_beranda', $data['id']);
        $this->audit->catat('urutkan', 'bagian_beranda', null, 'Urutkan ulang bagian beranda');
        app(LayananPengaturan::class)->naikkanVersiKonten();

        return $this->sukses('Urutan bagian beranda disimpan.');
    }
}
