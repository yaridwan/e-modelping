<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SimpanMenuRequest;
use App\Http\Requests\Admin\SimpanTautanSosialRequest;
use App\Layanan\LayananNavigasi;
use App\Layanan\LayananPengaturan;
use App\Layanan\LayananUrutan;
use App\Models\BabModul;
use App\Models\HalamanInformasi;
use App\Models\MenuNavigasi;
use App\Models\TautanSosial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends AdminController
{
    public function index(LayananNavigasi $navigasi): Response
    {
        $menu = MenuNavigasi::query()->with(['halaman:id,judul,slug,status', 'bab:id,nomor_romawi,judul,slug,status'])->orderBy('lokasi')->orderBy('urutan')->get();

        return Inertia::render('admin/menu/index', [
            'menu' => $menu->map(fn ($m) => [
                ...$m->only(['id', 'induk_id', 'lokasi', 'label', 'jenis_tautan', 'halaman_id', 'bab_id', 'url', 'ikon', 'buka_tab_baru', 'urutan', 'aktif']),
                'tujuan' => $navigasi->href($m),
                'target_label' => match ($m->jenis_tautan) {
                    'halaman' => $m->halaman?->judul, 'bab' => $m->bab ? 'Bab '.$m->bab->nomor_romawi.' '.$m->bab->judul : null, default => $m->url,
                },
            ])->all(),
            'sosial' => TautanSosial::query()->orderBy('urutan')->get(),
            'halaman' => HalamanInformasi::query()->orderBy('judul')->get(['id', 'judul', 'status'])->map(fn ($h) => ['id' => $h->id, 'label' => $h->judul.($h->status->value !== 'terbit' ? ' ('.$h->status->label().')' : '')])->all(),
            'bab' => BabModul::query()->orderBy('urutan')->get(['id', 'nomor_romawi', 'judul'])->map(fn ($b) => ['id' => $b->id, 'label' => "Bab {$b->nomor_romawi} · {$b->judul}"])->all(),
            'ikon' => SimpanMenuRequest::IKON,
            'platform' => SimpanTautanSosialRequest::PLATFORM,
        ]);
    }

    public function store(SimpanMenuRequest $request): RedirectResponse
    {
        $data = $request->dataTersaring();
        $m = new MenuNavigasi;
        $m->fill([...$data, 'urutan' => (int) MenuNavigasi::query()->where('lokasi', $data['lokasi'])->max('urutan') + 1])->save();
        $this->audit->catat('buat', 'menu_navigasi', $m->id, "Tambah menu {$m->label} ({$m->lokasi})");

        return $this->sukses('Menu ditambahkan.');
    }

    public function update(SimpanMenuRequest $request, MenuNavigasi $menu): RedirectResponse
    {
        $menu->fill($request->dataTersaring())->save();
        $this->audit->catatModel('perbarui', $menu, "Perbarui menu {$menu->label}");

        return $this->sukses('Menu disimpan.');
    }

    public function urutan(Request $request, LayananUrutan $urutan): RedirectResponse
    {
        $data = $request->validate(['lokasi' => ['required', Rule::in(['utama', 'footer'])], 'id' => ['required', 'array', 'min:1'], 'id.*' => ['integer']]);
        // Urutan unik tidak diwajibkan pada tabel menu; tetap diterapkan atomik per lokasi.
        $urutan->terapkan('menu_navigasi', $data['id'], ['lokasi' => $data['lokasi']]);
        app(LayananPengaturan::class)->naikkanVersiKonten();

        return $this->sukses('Urutan menu disimpan.');
    }

    public function destroy(MenuNavigasi $menu): RedirectResponse
    {
        $this->audit->catat('hapus', 'menu_navigasi', $menu->id, "Hapus menu {$menu->label}");
        $menu->delete();

        return $this->sukses('Menu dihapus.');
    }
}
