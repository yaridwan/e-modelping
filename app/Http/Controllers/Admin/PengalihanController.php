<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SimpanPengalihanRequest;
use App\Layanan\LayananPublikasi;
use App\Models\PengalihanUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PengalihanController extends AdminController
{
    public function index(Request $request): Response
    {
        $p = $this->parameterDaftar($request, ['jalur_lama', 'diperbarui_pada'], 'diperbarui_pada', 'desc');

        return Inertia::render('admin/pengalihan/index', [
            'pengalihan' => PengalihanUrl::query()
                ->when($p['q'] !== '', fn ($q) => $q->where(fn ($w) => $w->where('jalur_lama', 'like', self::pola($p['q']))->orWhere('jalur_baru', 'like', self::pola($p['q']))))
                ->orderBy($p['urut'], $p['arah'])->paginate($p['per'])->withQueryString(),
            'filter' => $p,
        ]);
    }

    public function store(SimpanPengalihanRequest $request, LayananPublikasi $publikasi): RedirectResponse
    {
        $d = $request->validated();
        $publikasi->validasiPengalihan($d['jalur_lama'], $d['jalur_baru']);
        $p = PengalihanUrl::query()->create([...$d, 'jalur_lama' => $publikasi->normalkan($d['jalur_lama']), 'jalur_baru' => $publikasi->normalkan($d['jalur_baru'])]);
        $this->audit->catat('buat', 'pengalihan_url', $p->id, "Tambah pengalihan {$p->jalur_lama} → {$p->jalur_baru}");

        return $this->sukses('Pengalihan ditambahkan.');
    }

    public function update(SimpanPengalihanRequest $request, PengalihanUrl $pengalihan, LayananPublikasi $publikasi): RedirectResponse
    {
        $d = $request->validated();
        $publikasi->validasiPengalihan($d['jalur_lama'], $d['jalur_baru'], $pengalihan->id);
        $pengalihan->fill([...$d, 'jalur_lama' => $publikasi->normalkan($d['jalur_lama']), 'jalur_baru' => $publikasi->normalkan($d['jalur_baru'])])->save();
        $this->audit->catatModel('perbarui', $pengalihan, "Perbarui pengalihan {$pengalihan->jalur_lama}");

        return $this->sukses('Pengalihan disimpan.');
    }

    public function destroy(PengalihanUrl $pengalihan): RedirectResponse
    {
        $this->audit->catat('hapus', 'pengalihan_url', $pengalihan->id, "Hapus pengalihan {$pengalihan->jalur_lama}");
        $pengalihan->delete();

        return $this->sukses('Pengalihan dihapus.');
    }
}
