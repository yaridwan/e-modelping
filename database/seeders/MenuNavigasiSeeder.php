<?php

namespace Database\Seeders;

use App\Models\HalamanInformasi;
use App\Models\MenuNavigasi;
use Illuminate\Database\Seeder;

class MenuNavigasiSeeder extends Seeder
{
    public function run(): void
    {
        if (MenuNavigasi::query()->exists()) {
            return;
        }
        $halaman = fn (string $kode) => HalamanInformasi::query()->where('kode_halaman', $kode)->value('id');

        $utama = [
            ['label' => 'Beranda', 'jenis_tautan' => 'internal', 'url' => '/', 'ikon' => 'house'],
            ['label' => 'Modul', 'jenis_tautan' => 'internal', 'url' => '/modul', 'ikon' => 'book-open'],
            ['label' => 'Fase PING', 'jenis_tautan' => 'internal', 'url' => '/fase/pre-class-greeting', 'ikon' => 'layers'],
            ['label' => 'Karakter', 'jenis_tautan' => 'internal', 'url' => '/karakter', 'ikon' => 'sparkles'],
            ['label' => 'Tentang', 'jenis_tautan' => 'halaman', 'halaman_id' => $halaman('tentang'), 'ikon' => 'info'],
            ['label' => 'Kontak', 'jenis_tautan' => 'halaman', 'halaman_id' => $halaman('kontak'), 'ikon' => 'mail'],
        ];
        $footer = [
            ['label' => 'Panduan Penggunaan', 'jenis_tautan' => 'halaman', 'halaman_id' => $halaman('panduan-penggunaan')],
            ['label' => 'Pertanyaan Umum', 'jenis_tautan' => 'internal', 'url' => '/pertanyaan-umum'],
            ['label' => 'Galeri', 'jenis_tautan' => 'internal', 'url' => '/galeri'],
            ['label' => 'Pesan untuk Guru', 'jenis_tautan' => 'halaman', 'halaman_id' => $halaman('pesan-untuk-guru')],
            ['label' => 'Kebijakan Privasi', 'jenis_tautan' => 'halaman', 'halaman_id' => $halaman('kebijakan-privasi')],
        ];
        foreach (['utama' => $utama, 'footer' => $footer] as $lokasi => $daftar) {
            foreach ($daftar as $i => $m) {
                MenuNavigasi::query()->create([...$m, 'lokasi' => $lokasi, 'urutan' => $i + 1, 'aktif' => true, 'buka_tab_baru' => false]);
            }
        }
    }
}
