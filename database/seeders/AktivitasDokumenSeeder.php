<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Data dasar: kosong-sengaja. Demo: DataDemoSeeder (data_contoh = true). */
class AktivitasDokumenSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('aktivitas_dokumen: kosong-sengaja pada data dasar.');
    }
}
