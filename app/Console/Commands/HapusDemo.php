<?php

namespace App\Console\Commands;

use App\Models\AktivitasDokumen;
use App\Models\KunjunganHalaman;
use App\Models\LogAktivitas;
use App\Models\PengalihanUrl;
use App\Models\PesanKontak;
use Database\Seeders\DataDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Hapus seluruh data demonstrasi (data_contoh = true) tanpa membuat ulang.
 * Aman untuk produksi: data nyata (data_contoh = false), konten, media, dan pengaturan tidak disentuh.
 * Dipakai setelah memindahkan database lokal ke server.
 */
class HapusDemo extends Command
{
    protected $signature = 'ping:hapus-demo {--force : Lewati konfirmasi}';

    protected $description = 'Hapus seluruh data demonstrasi (statistik, pesan, log contoh) tanpa membuat ulang; data nyata tidak disentuh';

    public function handle(): int
    {
        $jumlah = self::hitung();
        $total = array_sum($jumlah);

        $this->table(['Data demo', 'Jumlah'], collect($jumlah)->map(fn ($n, $k) => [$k, number_format($n, 0, ',', '.')])->values()->all());

        if ($total === 0) {
            $this->info('Tidak ada data demo. Tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Hapus permanen {$total} baris data demo? Data nyata tidak disentuh.")) {
            $this->warn('Dibatalkan.');

            return self::FAILURE;
        }

        $terhapus = self::hapus();
        LogAktivitas::query()->create([
            'aksi' => 'hapus_demo',
            'jenis_entitas' => 'sistem',
            'ringkasan' => 'Data demo dihapus lewat CLI ('.array_sum($terhapus).' baris)',
            'perubahan' => $terhapus,
            'data_contoh' => false,
            'dibuat_pada' => now(),
        ]);

        $this->info('Data demo dihapus: '.collect($terhapus)->map(fn ($n, $k) => "{$k} {$n}")->implode(', ').'.');

        return self::SUCCESS;
    }

    /** @return array<string, int> */
    public static function hitung(): array
    {
        return [
            'kunjungan_halaman' => KunjunganHalaman::query()->where('data_contoh', true)->count(),
            'aktivitas_dokumen' => AktivitasDokumen::query()->where('data_contoh', true)->count(),
            'pesan_kontak' => PesanKontak::query()->where('data_contoh', true)->count(),
            'log_aktivitas' => LogAktivitas::query()->where('data_contoh', true)->count(),
            'pengalihan_url' => PengalihanUrl::query()->where('jalur_lama', DataDemoSeeder::PENANDA_PENGALIHAN)->count(),
        ];
    }

    /**
     * Hapus semua data demo dalam satu transaksi.
     *
     * @return array<string, int> jumlah baris terhapus per tabel
     */
    public static function hapus(): array
    {
        return DB::transaction(fn () => [
            'kunjungan_halaman' => KunjunganHalaman::query()->where('data_contoh', true)->delete(),
            'aktivitas_dokumen' => AktivitasDokumen::query()->where('data_contoh', true)->delete(),
            'pesan_kontak' => PesanKontak::query()->where('data_contoh', true)->delete(),
            'log_aktivitas' => LogAktivitas::query()->where('data_contoh', true)->delete(),
            'pengalihan_url' => PengalihanUrl::query()->where('jalur_lama', DataDemoSeeder::PENANDA_PENGALIHAN)->delete(),
        ]);
    }
}
