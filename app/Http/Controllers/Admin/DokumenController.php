<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusKonten;
use App\Http\Requests\Admin\UnggahDokumenRequest;
use App\Layanan\LayananDokumen;
use App\Layanan\LayananMedia;
use App\Layanan\LayananPengaturan;
use App\Models\BabModul;
use App\Models\VersiDokumen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DokumenController extends AdminController
{
    public function index(Request $request): Response
    {
        $p = $this->parameterDaftar($request, ['diperbarui_pada', 'nomor_versi', 'judul_dokumen', 'status'], 'diperbarui_pada', 'desc');
        $status = in_array($request->query('status'), StatusKonten::nilai(), true) ? $request->query('status') : null;
        $lingkup = (string) $request->query('lingkup', '');

        $versi = VersiDokumen::query()->with(['bab:id,nomor_romawi,judul,status', 'berkas:id,ukuran_bita,jumlah_halaman,nama_asli'])
            ->when($p['q'] !== '', fn ($q) => $q->where(fn ($w) => $w->where('judul_dokumen', 'like', self::pola($p['q']))->orWhere('nomor_versi', 'like', self::pola($p['q']))))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($lingkup === 'modul_lengkap', fn ($q) => $q->where('jenis_dokumen', 'modul_lengkap'))
            ->when(ctype_digit($lingkup), fn ($q) => $q->where('bab_id', (int) $lingkup))
            ->orderByDesc('versi_aktif')->orderBy($p['urut'], $p['arah'])
            ->paginate($p['per'])->withQueryString()
            ->through(fn (VersiDokumen $v) => $this->ringkas($v));

        return Inertia::render('admin/dokumen/index', [
            'versi' => $versi,
            'filter' => $p + ['status' => $status, 'lingkup' => $lingkup],
            'bab' => BabModul::query()->orderBy('urutan')->get(['id', 'nomor_romawi', 'judul'])->map(fn ($b) => ['id' => $b->id, 'label' => "Bab {$b->nomor_romawi} · {$b->judul}"])->all(),
            'izin_unduh_global' => (bool) app(LayananPengaturan::class)->ambil()->izinkan_unduh,
        ]);
    }

    public function create(Request $request, LayananMedia $media): Response
    {
        return Inertia::render('admin/dokumen/unggah', [
            'bab' => BabModul::query()->orderBy('urutan')->get(['id', 'nomor_romawi', 'judul'])->map(fn ($b) => ['id' => $b->id, 'label' => "Bab {$b->nomor_romawi} · {$b->judul}"])->all(),
            'bab_terpilih' => $request->integer('bab') ?: null,
            'batas_mb' => $media->batasMb(),
            'panduan_penamaan' => app(LayananPengaturan::class)->ambil()->panduan_penamaan_versi,
        ]);
    }

    public function store(UnggahDokumenRequest $request, LayananDokumen $dokumen): RedirectResponse
    {
        $data = $request->validated();
        $bab = $data['jenis_dokumen'] === 'bab' ? BabModul::query()->findOrFail($data['bab_id']) : null;
        $versi = $dokumen->buatDraf($data['jenis_dokumen'], $bab, $request->file('berkas'), $data);

        if ($request->boolean('terbitkan_sekarang')) {
            $dokumen->aktifkan($versi, $request->user());

            return $this->sukses('PDF diunggah dan diterbitkan sebagai versi aktif.', "/admin/dokumen/{$versi->id}");
        }

        return $this->sukses('PDF diunggah sebagai draf. Tinjau pratinjau sebelum menerbitkan.', "/admin/dokumen/{$versi->id}");
    }

    public function show(VersiDokumen $versi, LayananDokumen $dokumen): Response
    {
        $versi->load(['bab:id,nomor_romawi,judul,status,slug', 'berkas', 'penerbit:id,nama_lengkap']);
        $lain = VersiDokumen::query()->where('jenis_dokumen', $versi->jenis_dokumen)
            ->when($versi->bab_id, fn ($q) => $q->where('bab_id', $versi->bab_id), fn ($q) => $q->whereNull('bab_id'))
            ->orderByDesc('dibuat_pada')->get();

        return Inertia::render('admin/dokumen/show', [
            'versi' => $this->ringkas($versi) + [
                'catatan_perubahan' => $versi->catatan_perubahan,
                'nama_asli' => $versi->berkas?->nama_asli,
                'checksum' => $versi->berkas?->checksum_sha256 ? substr($versi->berkas->checksum_sha256, 0, 16).'…' : null,
                'diterbitkan_oleh' => $versi->penerbit?->nama_lengkap,
                'url_pratinjau' => "/admin/dokumen/{$versi->id}/pratinjau",
                'url_publik' => $versi->status === StatusKonten::Terbit && $versi->versi_aktif ? "/dokumen/{$versi->id}/baca" : null,
                'izin_unduh_efektif' => $dokumen->izinUnduhEfektif($versi),
            ],
            'riwayat' => $lain->map(fn ($v) => $this->ringkas($v))->all(),
        ]);
    }

    public function update(Request $request, VersiDokumen $versi): RedirectResponse
    {
        $data = $request->validate([
            'judul_dokumen' => ['required', 'string', 'max:191'],
            'catatan_perubahan' => ['nullable', 'string', 'max:2000'],
            'izinkan_unduh' => ['boolean'],
        ]);
        $versi->fill($data)->forceFill(['diperbarui_oleh' => auth()->id()])->save();
        $this->audit->catatModel('perbarui', $versi, "Perbarui metadata {$versi->judul_dokumen} v{$versi->nomor_versi}");

        return $this->sukses('Metadata dokumen disimpan.');
    }

    public function aktifkan(Request $request, VersiDokumen $versi, LayananDokumen $dokumen): RedirectResponse
    {
        $dokumen->aktifkan($versi, $request->user());

        return $this->sukses("Versi {$versi->nomor_versi} diterbitkan. Versi aktif sebelumnya diarsipkan.");
    }

    public function arsipkan(VersiDokumen $versi, LayananDokumen $dokumen): RedirectResponse
    {
        $dokumen->arsipkan($versi);

        return $this->sukses("Versi {$versi->nomor_versi} diarsipkan dan tidak lagi tersedia untuk publik.");
    }

    public function destroy(VersiDokumen $versi, LayananDokumen $dokumen): RedirectResponse
    {
        $dokumen->hapusDraf($versi);

        return $this->sukses('Draf dokumen dihapus.', '/admin/dokumen');
    }

    /** Pratinjau berkas apa pun statusnya, hanya untuk admin, dengan header privat no-store. */
    public function pratinjau(Request $request, VersiDokumen $versi, LayananDokumen $dokumen): BinaryFileResponse
    {
        $versi->load(['berkas', 'bab']);

        return $dokumen->sajikan($versi, $request, 'baca', false);
    }

    private function ringkas(VersiDokumen $v): array
    {
        return [
            'id' => $v->id,
            'jenis_dokumen' => $v->jenis_dokumen,
            'lingkup' => $v->bab ? 'Bab '.$v->bab->nomor_romawi.' · '.$v->bab->judul : 'Modul lengkap',
            'bab_id' => $v->bab_id,
            'bab_terbit' => $v->bab ? $v->bab->status === StatusKonten::Terbit : true,
            'judul' => $v->judul_dokumen,
            'nomor_versi' => $v->nomor_versi,
            'status' => $v->status->value,
            'aktif' => $v->versi_aktif,
            'izinkan_unduh' => $v->izinkan_unduh,
            'ukuran_bita' => $v->berkas?->ukuran_bita,
            'jumlah_halaman' => $v->berkas?->jumlah_halaman,
            'diterbitkan_pada' => $v->diterbitkan_pada?->toIso8601String(),
            'dibuat_pada' => $v->dibuat_pada?->toIso8601String(),
            'diperbarui_pada' => $v->diperbarui_pada?->toIso8601String(),
        ];
    }
}
