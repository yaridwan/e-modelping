<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SimpanPertanyaanRequest;
use App\Layanan\LayananPengaturan;
use App\Layanan\LayananUrutan;
use App\Models\PertanyaanUmum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PertanyaanUmumController extends AdminController
{
    public function index(Request $request): Response
    {
        $p = $this->parameterDaftar($request, ['urutan', 'kategori', 'pertanyaan'], 'urutan');
        $aktif = in_array($request->query('aktif'), ['1', '0'], true) ? $request->query('aktif') : null;

        return Inertia::render('admin/faq/index', [
            'faq' => PertanyaanUmum::query()
                ->when($p['q'] !== '', fn ($q) => $q->where(fn ($w) => $w->where('pertanyaan', 'like', self::pola($p['q']))->orWhere('jawaban', 'like', self::pola($p['q']))))
                ->when($aktif !== null, fn ($q) => $q->where('aktif', $aktif === '1'))
                ->orderBy($p['urut'], $p['arah'])->paginate($p['per'])->withQueryString(),
            'filter' => $p + ['aktif' => $aktif],
            'semua_id' => PertanyaanUmum::query()->orderBy('urutan')->pluck('id'),
        ]);
    }

    public function store(SimpanPertanyaanRequest $request): RedirectResponse
    {
        $f = PertanyaanUmum::query()->create([...$request->validated(), 'urutan' => (int) PertanyaanUmum::query()->max('urutan') + 1]);
        $this->audit->catat('buat', 'pertanyaan_umum', $f->id, 'Tambah FAQ: '.$f->pertanyaan);

        return $this->sukses('Pertanyaan ditambahkan.');
    }

    public function update(SimpanPertanyaanRequest $request, PertanyaanUmum $faq): RedirectResponse
    {
        $faq->fill($request->validated())->save();
        $this->audit->catatModel('perbarui', $faq, 'Perbarui FAQ: '.$faq->pertanyaan);

        return $this->sukses('Pertanyaan disimpan.');
    }

    public function urutan(Request $request, LayananUrutan $urutan): RedirectResponse
    {
        $data = $request->validate(['id' => ['required', 'array', 'min:1'], 'id.*' => ['integer']]);
        $urutan->terapkan('pertanyaan_umum', $data['id']);
        app(LayananPengaturan::class)->naikkanVersiKonten();

        return $this->sukses('Urutan FAQ disimpan.');
    }

    public function destroy(PertanyaanUmum $faq): RedirectResponse
    {
        $this->audit->catat('hapus', 'pertanyaan_umum', $faq->id, 'Hapus FAQ: '.$faq->pertanyaan);
        $faq->delete();

        return $this->sukses('Pertanyaan dihapus.');
    }
}
