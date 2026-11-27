<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SimpanTautanSosialRequest;
use App\Models\TautanSosial;
use Illuminate\Http\RedirectResponse;

class TautanSosialController extends AdminController
{
    public function store(SimpanTautanSosialRequest $request): RedirectResponse
    {
        $t = TautanSosial::query()->create([...$request->validated(), 'ikon' => $request->validated()['nama_platform']]);
        $this->audit->catat('buat', 'tautan_sosial', $t->id, "Tambah tautan sosial {$t->label}");

        return $this->sukses('Tautan sosial ditambahkan.');
    }

    public function update(SimpanTautanSosialRequest $request, TautanSosial $tautan): RedirectResponse
    {
        $tautan->fill([...$request->validated(), 'ikon' => $request->validated()['nama_platform']])->save();
        $this->audit->catatModel('perbarui', $tautan, "Perbarui tautan sosial {$tautan->label}");

        return $this->sukses('Tautan sosial disimpan.');
    }

    public function destroy(TautanSosial $tautan): RedirectResponse
    {
        $this->audit->catat('hapus', 'tautan_sosial', $tautan->id, "Hapus tautan sosial {$tautan->label}");
        $tautan->delete();

        return $this->sukses('Tautan sosial dihapus.');
    }
}
