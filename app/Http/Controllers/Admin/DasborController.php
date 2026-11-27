<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusKonten;
use App\Enums\StatusPesan;
use App\Layanan\LayananInventaris;
use App\Layanan\LayananMedia;
use App\Layanan\LayananStatistik;
use App\Models\BabModul;
use App\Models\BerkasMedia;
use App\Models\LogAktivitas;
use App\Models\PesanKontak;
use App\Models\SubbabModul;
use App\Models\VersiDokumen;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DasborController extends AdminController
{
    public function index(Request $request, LayananStatistik $statistik, LayananInventaris $inventaris, LayananMedia $media): Response
    {
        $hari = in_array((int) $request->query('hari'), [7, 30, 90], true) ? (int) $request->query('hari') : 30;
        $periode = $statistik->periode($request->query('dari'), $request->query('sampai'), $hari);
        $demo = $request->boolean('demo') && $statistik->adaDataDemo();
        [$dari, $sampai] = [$periode['dari'], $periode['sampai']];

        $terakhirMateri = collect([BabModul::query()->max('diperbarui_pada'), SubbabModul::query()->max('diperbarui_pada')])->filter()->max();

        return Inertia::render('admin/dasbor', [
            'filter' => ['hari' => $hari, 'dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString(), 'demo' => $demo, 'ada_demo' => $statistik->adaDataDemo()],
            'widget' => [
                'bab_terbit' => BabModul::query()->terbit()->count(),
                'bab_total' => BabModul::query()->count(),
                'subbab_terbit' => SubbabModul::query()->terbit()->count(),
                'subbab_total' => SubbabModul::query()->count(),
                'pdf_aktif' => VersiDokumen::query()->where('versi_aktif', true)->count(),
                'ukuran_media' => $media->totalUkuran(),
                'pesan_baru' => PesanKontak::query()->where('status', StatusPesan::Baru)->where('data_contoh', false)->count(),
                'materi_diperbarui' => $terakhirMateri ? CarbonImmutable::parse($terakhirMateri)->toIso8601String() : null,
                ...$statistik->ringkasan($dari, $sampai, $demo),
            ],
            'grafik' => [
                'kunjungan' => $statistik->trenKunjungan($dari, $sampai, $demo),
                'dokumen' => $statistik->trenDokumen($dari, $sampai, $demo),
                'bab_populer' => $statistik->babPopuler($dari, $sampai, $demo),
                'status_konten' => collect(StatusKonten::cases())->map(fn ($s) => [
                    'status' => $s->label(),
                    'bab' => BabModul::query()->where('status', $s)->count(),
                    'subbab' => SubbabModul::query()->where('status', $s)->count(),
                ])->all(),
                'jenis_media' => BerkasMedia::query()->selectRaw('jenis_media as jenis, COUNT(*) as jumlah')->groupBy('jenis_media')->get()
                    ->map(fn ($r) => ['jenis' => $r->jenis instanceof \BackedEnum ? $r->jenis->value : $r->jenis, 'jumlah' => (int) $r->jumlah])->all(),
            ],
            'tabel' => [
                'dokumen' => VersiDokumen::query()->with('bab:id,nomor_romawi,judul')->orderByDesc('versi_aktif')->orderByDesc('diperbarui_pada')->limit(6)->get()
                    ->map(fn ($v) => ['id' => $v->id, 'judul' => $v->judul_dokumen, 'nomor_versi' => $v->nomor_versi, 'status' => $v->status->value, 'aktif' => $v->versi_aktif, 'bab' => $v->bab ? 'Bab '.$v->bab->nomor_romawi : 'Modul lengkap', 'diperbarui_pada' => $v->diperbarui_pada?->toIso8601String()])->all(),
                'pesan' => PesanKontak::query()->where('data_contoh', $demo)->orderByDesc('dibuat_pada')->limit(5)->get(['id', 'nama_pengirim', 'subjek', 'status', 'dibuat_pada', 'data_contoh'])
                    ->map(fn ($p) => ['id' => $p->id, 'nama' => $p->nama_pengirim, 'subjek' => $p->subjek, 'status' => $p->status->value, 'demo' => $p->data_contoh, 'dibuat_pada' => $p->dibuat_pada?->toIso8601String()])->all(),
                'aktivitas' => LogAktivitas::query()->with('pengguna:id,nama_lengkap')->where('data_contoh', $demo)->orderByDesc('dibuat_pada')->limit(8)->get()
                    ->map(fn ($l) => ['id' => $l->id, 'aksi' => $l->aksi, 'ringkasan' => $l->ringkasan, 'pengguna' => $l->pengguna?->nama_lengkap, 'dibuat_pada' => $l->dibuat_pada?->toIso8601String()])->all(),
                'inventaris' => $inventaris->matriks(),
            ],
            'kesehatan' => $inventaris->kesehatan(),
        ]);
    }
}
