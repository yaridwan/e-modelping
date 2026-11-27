<?php

namespace App\Http\Controllers\Admin;

use App\Layanan\LayananPengaturan;
use App\Layanan\LayananStatistik;
use App\Support\EksporCsv;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatistikController extends AdminController
{
    public function index(Request $request, LayananStatistik $statistik, LayananPengaturan $pengaturan): Response
    {
        $periode = $statistik->periode($request->query('dari'), $request->query('sampai'), 30);
        $demo = $request->boolean('demo') && $statistik->adaDataDemo();
        [$dari, $sampai] = [$periode['dari'], $periode['sampai']];
        $p = $pengaturan->ambil();

        return Inertia::render('admin/statistik/index', [
            'filter' => ['dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString(), 'demo' => $demo, 'ada_demo' => $statistik->adaDataDemo()],
            'ringkasan' => $statistik->ringkasan($dari, $sampai, $demo),
            'kunjungan' => $statistik->trenKunjungan($dari, $sampai, $demo),
            'dokumen' => $statistik->trenDokumen($dari, $sampai, $demo),
            'bab_populer' => $statistik->babPopuler($dari, $sampai, $demo),
            'dokumen_populer' => $statistik->dokumenPopuler($dari, $sampai, $demo),
            'halaman_populer' => $statistik->halamanPopuler($dari, $sampai, $demo),
            'perangkat' => $statistik->distribusiPerangkat($dari, $sampai, $demo),
            'kebijakan' => [
                'aktif' => (bool) $p->statistik_aktif, 'kecualikan_admin' => (bool) $p->kecualikan_admin, 'kecualikan_bot' => (bool) $p->kecualikan_bot,
                'retensi_hari' => (int) $p->retensi_statistik_hari, 'zona_waktu' => $pengaturan->zonaWaktu(),
            ],
        ]);
    }

    public function ekspor(Request $request, LayananStatistik $statistik): StreamedResponse
    {
        $periode = $statistik->periode($request->query('dari'), $request->query('sampai'), 30);
        $demo = $request->boolean('demo');
        $kunjungan = collect($statistik->trenKunjungan($periode['dari'], $periode['sampai'], $demo))->keyBy('tanggal');
        $dokumen = collect($statistik->trenDokumen($periode['dari'], $periode['sampai'], $demo))->keyBy('tanggal');
        $this->audit->catat('ekspor', 'statistik', null, 'Ekspor CSV statistik harian', ['dari' => $periode['dari']->toDateString(), 'sampai' => $periode['sampai']->toDateString(), 'demo' => $demo]);

        $baris = $kunjungan->map(fn ($k, $t) => [$t, $k['kunjungan'], $k['pengunjung'], $dokumen[$t]['baca'] ?? 0, $dokumen[$t]['unduh'] ?? 0, $demo ? 'Demo' : 'Nyata'])->values()->all();

        return EksporCsv::unduh('statistik-'.$periode['dari']->format('Ymd').'-'.$periode['sampai']->format('Ymd').($demo ? '-demo' : '').'.csv',
            ['Tanggal', 'Kunjungan', 'Pengunjung unik (perkiraan)', 'Baca dokumen', 'Unduhan dilayani', 'Jenis data'], $baris);
    }
}
