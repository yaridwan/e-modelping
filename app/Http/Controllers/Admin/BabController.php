<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusKonten;
use App\Http\Requests\Admin\SimpanBabRequest;
use App\Layanan\LayananPublikasi;
use App\Layanan\LayananSanitasiHtml;
use App\Layanan\LayananWaktuBaca;
use App\Models\BabModul;
use App\Models\LogAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Struktur sembilan bab dijaga: bab tidak dapat dibuat atau dihapus, hanya disunting/diubah status. */
class BabController extends AdminController
{
    public function index(Request $request): Response
    {
        $p = $this->parameterDaftar($request, ['urutan', 'judul', 'status', 'diperbarui_pada'], 'urutan');
        $status = in_array($request->query('status'), StatusKonten::nilai(), true) ? $request->query('status') : null;

        $bab = BabModul::query()
            ->withCount(['subbab', 'subbab as subbab_terbit_count' => fn ($q) => $q->where('status', 'terbit')])
            ->with('versiAktif:id,bab_id,nomor_versi')
            ->when($p['q'] !== '', fn ($q) => $q->where(fn ($w) => $w->where('judul', 'like', self::pola($p['q']))->orWhere('ringkasan', 'like', self::pola($p['q']))))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy($p['urut'], $p['arah'])
            ->paginate($p['per'])->withQueryString()
            ->through(fn (BabModul $b) => [
                'id' => $b->id, 'nomor' => $b->nomor_bab, 'romawi' => $b->nomor_romawi, 'judul' => $b->judul, 'slug' => $b->slug,
                'status' => $b->status->value, 'subbab' => $b->subbab_count, 'subbab_terbit' => $b->subbab_terbit_count,
                'versi_aktif' => $b->versiAktif?->nomor_versi, 'diperbarui_pada' => $b->diperbarui_pada?->toIso8601String(),
            ]);

        return Inertia::render('admin/bab/index', ['bab' => $bab, 'filter' => $p + ['status' => $status]]);
    }

    public function edit(BabModul $bab): Response
    {
        $bab->load(['subbab', 'gambarSampul', 'gambarOg', 'versiDokumen' => fn ($q) => $q->orderByDesc('dibuat_pada')]);
        $kata = LayananWaktuBaca::jumlahKata((string) $bab->pengantar, ...$bab->subbab->where('status', StatusKonten::Terbit)->map(fn ($s) => $s->isi.' '.$s->contoh_praktik)->all());

        return Inertia::render('admin/bab/edit', [
            'bab' => [
                ...$bab->only(['id', 'nomor_bab', 'nomor_romawi', 'judul', 'slug', 'ringkasan', 'pengantar', 'judul_seo', 'deskripsi_seo', 'gambar_sampul_id', 'gambar_og_id', 'metadata_sumber']),
                'status' => $bab->status->value,
                'tujuan_pembelajaran' => $bab->tujuan_pembelajaran ?? [],
                'poin_penting' => $bab->poin_penting ?? [],
                'gambar_sampul' => $bab->gambarSampul ? ['id' => $bab->gambarSampul->id, 'url' => $bab->gambarSampul->urlAdmin(), 'judul' => $bab->gambarSampul->judul] : null,
                'gambar_og' => $bab->gambarOg ? ['id' => $bab->gambarOg->id, 'url' => $bab->gambarOg->urlAdmin(), 'judul' => $bab->gambarOg->judul] : null,
                'diterbitkan_pada' => $bab->diterbitkan_pada?->toIso8601String(),
                'diperbarui_pada' => $bab->diperbarui_pada?->toIso8601String(),
                'waktu_baca_menit' => LayananWaktuBaca::menit($kata),
            ],
            'subbab' => $bab->subbab->map(fn ($s) => ['id' => $s->id, 'kode' => $s->kode_subbab, 'judul' => $s->judul, 'slug' => $s->slug, 'status' => $s->status->value, 'urutan' => $s->urutan, 'diperbarui_pada' => $s->diperbarui_pada?->toIso8601String()])->all(),
            'dokumen' => $bab->versiDokumen->map(fn ($v) => ['id' => $v->id, 'nomor_versi' => $v->nomor_versi, 'judul' => $v->judul_dokumen, 'status' => $v->status->value, 'aktif' => $v->versi_aktif])->all(),
            'riwayat' => LogAktivitas::query()->with('pengguna:id,nama_lengkap')
                ->where(fn ($q) => $q->where(fn ($w) => $w->where('jenis_entitas', 'bab_modul')->where('entitas_id', $bab->id))
                    ->orWhere(fn ($w) => $w->where('jenis_entitas', 'subbab_modul')->whereIn('entitas_id', $bab->subbab->pluck('id'))))
                ->orderByDesc('dibuat_pada')->limit(15)->get()
                ->map(fn ($l) => ['id' => $l->id, 'aksi' => $l->aksi, 'ringkasan' => $l->ringkasan, 'pengguna' => $l->pengguna?->nama_lengkap, 'dibuat_pada' => $l->dibuat_pada?->toIso8601String()])->all(),
        ]);
    }

    public function update(SimpanBabRequest $request, BabModul $bab, LayananSanitasiHtml $sanitasi, LayananPublikasi $publikasi): RedirectResponse
    {
        $data = $request->validated();
        $slugLama = $bab->slug;

        DB::transaction(function () use ($bab, $data, $sanitasi, $publikasi, $slugLama) {
            $bab->fill([
                ...$data,
                'pengantar' => $sanitasi->bersihkan($data['pengantar'] ?? null),
                'tujuan_pembelajaran' => array_values(array_filter($data['tujuan_pembelajaran'] ?? [])),
                'poin_penting' => array_values(array_filter($data['poin_penting'] ?? [])),
            ]);
            $bab->forceFill(['diperbarui_oleh' => auth()->id()])->save();
            $this->audit->catatModel('perbarui', $bab, "Perbarui Bab {$bab->nomor_romawi} {$bab->judul}");

            if ($slugLama !== $bab->slug) {
                $publikasi->catatPengalihan('/modul/'.$slugLama, '/modul/'.$bab->slug);
            }
        });

        return $this->sukses('Bab berhasil disimpan.'.($slugLama !== $bab->slug ? ' URL lama dialihkan (301) ke URL baru.' : ''));
    }

    public function status(Request $request, BabModul $bab, LayananPublikasi $publikasi): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(StatusKonten::nilai())]]);
        $publikasi->ubahStatus($bab, StatusKonten::from($data['status']), "Bab {$bab->nomor_romawi} {$bab->judul}");

        return $this->sukses('Status bab diubah menjadi '.StatusKonten::from($data['status'])->label().'.');
    }

    /** Pratinjau admin (termasuk subbab draf) dengan header noindex & no-store. */
    public function pratinjau(BabModul $bab): Response
    {
        $bab->load(['subbab.media', 'gambarSampul']);

        return Inertia::render('admin/bab/pratinjau', [
            'bab' => [
                'id' => $bab->id, 'romawi' => $bab->nomor_romawi, 'judul' => $bab->judul, 'ringkasan' => $bab->ringkasan,
                'pengantar' => $bab->pengantar, 'status' => $bab->status->value,
                'tujuan_pembelajaran' => $bab->tujuan_pembelajaran ?? [], 'poin_penting' => $bab->poin_penting ?? [],
                'subbab' => $bab->subbab->map(fn ($s) => ['id' => $s->id, 'kode' => $s->kode_subbab, 'judul' => $s->judul, 'slug' => $s->slug, 'status' => $s->status->value, 'ringkasan' => $s->ringkasan, 'isi' => $s->isi, 'contoh_praktik' => $s->contoh_praktik])->all(),
            ],
        ]);
    }
}
