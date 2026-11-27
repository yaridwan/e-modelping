<?php

namespace App\Layanan;

use App\Models\AktivitasDokumen;
use App\Models\KunjunganHalaman;
use App\Models\PesanKontak;
use App\Models\TokenAturUlangSandi;
use App\Models\VersiDokumen;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Statistik aplikasi berbasis data pertama (tanpa pelacak pihak ketiga).
 *
 * Definisi:
 * - Kunjungan: tampilan halaman publik (respons 200) oleh non-bot dan non-admin (sesuai pengaturan),
 *   dihitung satu kali per sidik pengunjung, per jalur, per tanggal lokal.
 * - Pengunjung unik perkiraan: jumlah sidik pseudonim berbeda pada periode. Bukan orang unik absolut.
 * - Baca/unduh dokumen: respons berkas yang dilayani server (bukan bukti selesai dibaca/disimpan),
 *   satu kali per sidik, per versi, per jenis, per tanggal lokal. Permintaan Range lanjutan diabaikan.
 * - Data demonstrasi (data_contoh = true) tidak pernah dicampur dengan data nyata.
 */
class LayananStatistik
{
    public function __construct(
        private readonly LayananPengaturan $pengaturan,
        private readonly LayananSidik $sidik,
    ) {}

    public function bolehCatat(Request $request): bool
    {
        $p = $this->pengaturan->ambil();
        if (! $p->statistik_aktif) {
            return false;
        }
        if ($p->kecualikan_bot && $this->sidik->bot($request)) {
            return false;
        }
        if ($p->kecualikan_admin && $request->user()) {
            return false;
        }

        return ! in_array(strtolower((string) $request->headers->get('Purpose', $request->headers->get('Sec-Purpose', ''))), ['prefetch', 'prerender'], true);
    }

    public function catatKunjungan(Request $request, string $jenis, ?int $babId = null, ?int $halamanId = null): void
    {
        if (! $this->bolehCatat($request)) {
            return;
        }

        $sekarang = now();
        KunjunganHalaman::query()->insertOrIgnore([
            'jenis_halaman' => Str::limit($jenis, 30, ''),
            'bab_id' => $babId,
            'halaman_id' => $halamanId,
            'jalur_halaman' => Str::limit('/'.ltrim($request->path(), '/'), 190, ''),
            'asal_domain' => $this->asalDomain($request),
            'sidik_pengunjung' => $this->sidik->pengunjung($request),
            'tanggal_kunjungan' => $sekarang->timezone($this->pengaturan->zonaWaktu())->toDateString(),
            'jenis_perangkat' => $this->sidik->perangkat($request),
            'data_contoh' => false,
            'dibuat_pada' => $sekarang,
        ]);
    }

    public function catatAktivitasDokumen(Request $request, VersiDokumen $versi, string $jenis): void
    {
        if (! $this->bolehCatat($request)) {
            return;
        }

        $sekarang = now();
        AktivitasDokumen::query()->insertOrIgnore([
            'versi_dokumen_id' => $versi->id,
            'jenis_aktivitas' => $jenis === 'unduh' ? 'unduh' : 'baca',
            'sidik_pengunjung' => $this->sidik->pengunjung($request),
            'jenis_perangkat' => $this->sidik->perangkat($request),
            'tanggal_aktivitas' => $sekarang->timezone($this->pengaturan->zonaWaktu())->toDateString(),
            'data_contoh' => false,
            'dibuat_pada' => $sekarang,
        ]);
    }

    /** Hanya nama host perujuk eksternal, tanpa jalur/query. */
    private function asalDomain(Request $request): ?string
    {
        $perujuk = (string) $request->headers->get('referer', '');
        $host = $perujuk !== '' ? parse_url($perujuk, PHP_URL_HOST) : null;
        if (! $host || $host === $request->getHost()) {
            return null;
        }

        return Str::limit(strtolower($host), 190, '');
    }

    // ------------------------------------------------------------------ agregasi

    /** @return array{dari: CarbonImmutable, sampai: CarbonImmutable} tanggal lokal inklusif */
    public function periode(?string $dari, ?string $sampai, int $hariBawaan = 30): array
    {
        $tz = $this->pengaturan->zonaWaktu();
        $hariIni = CarbonImmutable::now($tz)->startOfDay();
        try {
            $s = $sampai ? CarbonImmutable::createFromFormat('Y-m-d', $sampai, $tz)->startOfDay() : $hariIni;
            $d = $dari ? CarbonImmutable::createFromFormat('Y-m-d', $dari, $tz)->startOfDay() : $s->subDays($hariBawaan - 1);
        } catch (\Throwable) {
            $s = $hariIni;
            $d = $s->subDays($hariBawaan - 1);
        }
        if ($d->gt($s)) {
            [$d, $s] = [$s, $d];
        }
        if ($d->diffInDays($s) > 366) {
            $d = $s->subDays(366);
        }

        return ['dari' => $d, 'sampai' => $s];
    }

    public function ringkasan(CarbonImmutable $dari, CarbonImmutable $sampai, bool $demo): array
    {
        $kunjungan = KunjunganHalaman::query()->where('data_contoh', $demo)->whereBetween('tanggal_kunjungan', [$dari->toDateString(), $sampai->toDateString()]);
        $aktivitas = AktivitasDokumen::query()->where('data_contoh', $demo)->whereBetween('tanggal_aktivitas', [$dari->toDateString(), $sampai->toDateString()]);

        return [
            'kunjungan' => (clone $kunjungan)->count(),
            'pengunjung_unik_perkiraan' => (clone $kunjungan)->distinct()->count('sidik_pengunjung'),
            'baca_dokumen' => (clone $aktivitas)->where('jenis_aktivitas', 'baca')->count(),
            'unduh_dokumen' => (clone $aktivitas)->where('jenis_aktivitas', 'unduh')->count(),
        ];
    }

    /** @return list<array{tanggal: string, kunjungan: int, pengunjung: int}> */
    public function trenKunjungan(CarbonImmutable $dari, CarbonImmutable $sampai, bool $demo): array
    {
        $baris = KunjunganHalaman::query()
            ->selectRaw('tanggal_kunjungan as tanggal, COUNT(*) as kunjungan, COUNT(DISTINCT sidik_pengunjung) as pengunjung')
            ->where('data_contoh', $demo)
            ->whereBetween('tanggal_kunjungan', [$dari->toDateString(), $sampai->toDateString()])
            ->groupBy('tanggal_kunjungan')
            ->get()
            ->keyBy(fn ($b) => substr((string) $b->tanggal, 0, 10));

        return $this->isiTanggal($dari, $sampai, fn (string $t) => [
            'kunjungan' => (int) ($baris[$t]->kunjungan ?? 0),
            'pengunjung' => (int) ($baris[$t]->pengunjung ?? 0),
        ]);
    }

    /** @return list<array{tanggal: string, baca: int, unduh: int}> */
    public function trenDokumen(CarbonImmutable $dari, CarbonImmutable $sampai, bool $demo): array
    {
        $baris = AktivitasDokumen::query()
            ->selectRaw("tanggal_aktivitas as tanggal, SUM(jenis_aktivitas = 'baca') as baca, SUM(jenis_aktivitas = 'unduh') as unduh")
            ->where('data_contoh', $demo)
            ->whereBetween('tanggal_aktivitas', [$dari->toDateString(), $sampai->toDateString()])
            ->groupBy('tanggal_aktivitas')
            ->get()
            ->keyBy(fn ($b) => substr((string) $b->tanggal, 0, 10));

        return $this->isiTanggal($dari, $sampai, fn (string $t) => [
            'baca' => (int) ($baris[$t]->baca ?? 0),
            'unduh' => (int) ($baris[$t]->unduh ?? 0),
        ]);
    }

    /** @return list<array{bab_id: int, label: string, judul: string, kunjungan: int}> */
    public function babPopuler(CarbonImmutable $dari, CarbonImmutable $sampai, bool $demo, int $batas = 9): array
    {
        return DB::table('kunjungan_halaman as k')
            ->join('bab_modul as b', 'b.id', '=', 'k.bab_id')
            ->selectRaw('b.id as bab_id, b.nomor_romawi, b.judul, COUNT(*) as kunjungan')
            ->where('k.data_contoh', $demo)
            ->whereBetween('k.tanggal_kunjungan', [$dari->toDateString(), $sampai->toDateString()])
            ->groupBy('b.id', 'b.nomor_romawi', 'b.judul')
            ->orderByDesc('kunjungan')
            ->limit($batas)
            ->get()
            ->map(fn ($b) => ['bab_id' => (int) $b->bab_id, 'label' => 'Bab '.$b->nomor_romawi, 'judul' => $b->judul, 'kunjungan' => (int) $b->kunjungan])
            ->all();
    }

    /** @return list<array{versi_id: int, judul: string, nomor_versi: string, baca: int, unduh: int}> */
    public function dokumenPopuler(CarbonImmutable $dari, CarbonImmutable $sampai, bool $demo, int $batas = 10): array
    {
        return DB::table('aktivitas_dokumen as a')
            ->join('versi_dokumen as v', 'v.id', '=', 'a.versi_dokumen_id')
            ->selectRaw("v.id as versi_id, v.judul_dokumen, v.nomor_versi, SUM(a.jenis_aktivitas = 'baca') as baca, SUM(a.jenis_aktivitas = 'unduh') as unduh")
            ->where('a.data_contoh', $demo)
            ->whereBetween('a.tanggal_aktivitas', [$dari->toDateString(), $sampai->toDateString()])
            ->groupBy('v.id', 'v.judul_dokumen', 'v.nomor_versi')
            ->orderByDesc('baca')
            ->limit($batas)
            ->get()
            ->map(fn ($r) => ['versi_id' => (int) $r->versi_id, 'judul' => $r->judul_dokumen, 'nomor_versi' => $r->nomor_versi, 'baca' => (int) $r->baca, 'unduh' => (int) $r->unduh])
            ->all();
    }

    /** @return list<array{nama: string, jumlah: int}> */
    public function distribusiPerangkat(CarbonImmutable $dari, CarbonImmutable $sampai, bool $demo): array
    {
        return KunjunganHalaman::query()
            ->selectRaw('jenis_perangkat as nama, COUNT(*) as jumlah')
            ->where('data_contoh', $demo)
            ->whereBetween('tanggal_kunjungan', [$dari->toDateString(), $sampai->toDateString()])
            ->groupBy('jenis_perangkat')
            ->orderByDesc('jumlah')
            ->get()
            ->map(fn ($r) => ['nama' => $r->nama, 'jumlah' => (int) $r->jumlah])
            ->all();
    }

    /** @return list<array{halaman: string, jumlah: int}> */
    public function halamanPopuler(CarbonImmutable $dari, CarbonImmutable $sampai, bool $demo, int $batas = 10): array
    {
        return KunjunganHalaman::query()
            ->selectRaw('jalur_halaman as halaman, COUNT(*) as jumlah')
            ->where('data_contoh', $demo)
            ->whereBetween('tanggal_kunjungan', [$dari->toDateString(), $sampai->toDateString()])
            ->groupBy('jalur_halaman')
            ->orderByDesc('jumlah')
            ->limit($batas)
            ->get()
            ->map(fn ($r) => ['halaman' => $r->halaman, 'jumlah' => (int) $r->jumlah])
            ->all();
    }

    public function adaDataDemo(): bool
    {
        return KunjunganHalaman::query()->where('data_contoh', true)->exists()
            || AktivitasDokumen::query()->where('data_contoh', true)->exists();
    }

    /** Hapus data statistik dan pesan yang melewati masa retensi. @return array<string, int> */
    public function bersihkanRetensi(): array
    {
        $p = $this->pengaturan->ambil();
        $batasStat = now()->subDays(max(30, (int) $p->retensi_statistik_hari))->toDateString();
        $batasPesan = now()->subDays(max(30, (int) $p->retensi_pesan_hari));

        return [
            'kunjungan_halaman' => KunjunganHalaman::query()->where('tanggal_kunjungan', '<', $batasStat)->delete(),
            'aktivitas_dokumen' => AktivitasDokumen::query()->where('tanggal_aktivitas', '<', $batasStat)->delete(),
            'pesan_kontak' => PesanKontak::query()->whereIn('status', ['arsip', 'ditindaklanjuti'])->where('dibuat_pada', '<', $batasPesan)->delete(),
            'token_atur_ulang_sandi' => TokenAturUlangSandi::query()->where('kedaluwarsa_pada', '<', now()->subDay())->delete(),
        ];
    }

    /**
     * @param  \Closure(string): array<string, int>  $nilai
     * @return list<array<string, mixed>>
     */
    private function isiTanggal(CarbonImmutable $dari, CarbonImmutable $sampai, \Closure $nilai): array
    {
        $hasil = [];
        for ($t = $dari; $t->lte($sampai); $t = $t->addDay()) {
            $kunci = $t->toDateString();
            $hasil[] = ['tanggal' => $kunci] + $nilai($kunci);
        }

        return $hasil;
    }
}
