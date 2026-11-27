<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Tabel `riwayat_migrasi` diisi OLEH MIGRATOR, bukan seeder. Seeder ini hanya memverifikasi
 * bahwa catatan migrasi ada; tidak menyisipkan riwayat fiktif.
 */
class RiwayatMigrasiSeeder extends Seeder
{
    public function run(): void
    {
        $tabel = (string) config('database.migrations.table');
        if (! Schema::hasTable($tabel) || DB::table($tabel)->count() === 0) {
            throw new RuntimeException("Tabel {$tabel} kosong. Jalankan `php artisan migrate` sebelum seeding.");
        }
        $this->command?->info('Riwayat migrasi terverifikasi: '.DB::table($tabel)->count().' catatan (tidak ada data yang ditambahkan).');
    }
}
