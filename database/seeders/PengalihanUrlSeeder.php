<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Data dasar: kosong; pengalihan dibuat otomatis saat slug berubah. Contoh ada di DataDemoSeeder. */
class PengalihanUrlSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('pengalihan_url: kosong pada data dasar (otomatis terisi saat slug diubah).');
    }
}
