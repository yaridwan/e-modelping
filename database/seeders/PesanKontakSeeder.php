<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Data dasar: kosong-sengaja. Contoh pesan (data_contoh = true) dibuat oleh DataDemoSeeder. */
class PesanKontakSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('pesan_kontak: kosong-sengaja pada data dasar; contoh ada di DataDemoSeeder.');
    }
}
