<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SimpanReferensiRequest;
use App\Models\ReferensiPustaka;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferensiController extends AdminController
{
    public function index(Request $request): Response
    {
        $p = $this->parameterDaftar($request, ['urutan', 'penulis', 'tahun', 'status_verifikasi'], 'penulis');
        $status = (string) $request->query('status_verifikasi', '');

        return Inertia::render('admin/referensi/index', [
            'referensi' => ReferensiPustaka::query()
                ->when($p['q'] !== '', fn ($q) => $q->where(fn ($w) => $w->where('penulis', 'like', self::pola($p['q']))->orWhere('judul', 'like', self::pola($p['q']))))
                ->when(in_array($status, ['belum_diverifikasi', 'tercantum_di_sumber', 'terverifikasi', 'perlu_koreksi'], true), fn ($q) => $q->where('status_verifikasi', $status))
                ->orderBy($p['urut'], $p['arah'])->paginate($p['per'])->withQueryString()
                ->through(fn ($r) => [...$r->toArray(), 'tanggal_akses' => $r->tanggal_akses?->toDateString()]),
            'filter' => $p + ['status_verifikasi' => $status],
        ]);
    }

    public function store(SimpanReferensiRequest $request): RedirectResponse
    {
        $r = ReferensiPustaka::query()->create($request->validated());
        $this->audit->catat('buat', 'referensi_pustaka', $r->id, 'Tambah referensi: '.$r->penulis.' ('.$r->tahun.')');

        return $this->sukses('Referensi ditambahkan.');
    }

    public function update(SimpanReferensiRequest $request, ReferensiPustaka $referensi): RedirectResponse
    {
        $referensi->fill($request->validated())->save();
        $this->audit->catatModel('perbarui', $referensi, 'Perbarui referensi: '.$referensi->penulis);

        return $this->sukses('Referensi disimpan.');
    }

    public function destroy(ReferensiPustaka $referensi): RedirectResponse
    {
        $this->audit->catat('hapus', 'referensi_pustaka', $referensi->id, 'Hapus referensi: '.$referensi->penulis);
        $referensi->delete();

        return $this->sukses('Referensi dihapus.');
    }
}
