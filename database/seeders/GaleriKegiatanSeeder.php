<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Sengaja kosong pada data dasar: dokumentasi kegiatan (terutama yang memuat anak) hanya
 * diterbitkan pemilik bila izin publikasi dimiliki. Foto anak dalam PDF sumber tidak diekstrak.
 * Bagian galeri di beranda otomatis tersembunyi saat kosong.
 */
class GaleriKegiatanSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('galeri_kegiatan: kosong-sengaja (menunggu media berizin dari pemilik).');
    }
}
