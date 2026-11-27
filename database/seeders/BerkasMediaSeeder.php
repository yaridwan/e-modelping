<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Data dasar tidak memasukkan media unggahan. Logo, favicon, dan gambar Open Graph bawaan
 * berupa aset proyek di public/images (dipakai sebagai cadangan saat pengaturan kosong).
 * Berkas PDF modul dibuat oleh VersiDokumenSeeder (yang juga membuat baris berkas_media).
 */
class BerkasMediaSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('berkas_media: diisi oleh VersiDokumenSeeder (PDF) dan unggahan admin; tidak ada foto identitas rekaan.');
    }
}
