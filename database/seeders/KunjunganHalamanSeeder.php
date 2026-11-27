<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Data dasar: kosong-sengaja (tidak ada pengunjung fiktif di produksi). Demo: DataDemoSeeder. */
class KunjunganHalamanSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('kunjungan_halaman: kosong-sengaja pada data dasar.');
    }
}
