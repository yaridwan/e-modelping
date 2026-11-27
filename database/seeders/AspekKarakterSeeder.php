<?php

namespace Database\Seeders;

use App\Models\AspekKarakter;
use Illuminate\Database\Seeder;

class AspekKarakterSeeder extends Seeder
{
    public function run(): void
    {
        foreach (MateriSumber::data()['aspek'] as $i => $a) {
            if (AspekKarakter::query()->where('kode_aspek', $a['kode_aspek'])->exists()) {
                continue;
            }
            $aspek = new AspekKarakter;
            $aspek->forceFill([
                'kode_aspek' => $a['kode_aspek'], 'nama' => $a['nama'], 'slug' => $a['slug'], 'deskripsi' => $a['deskripsi'],
                'contoh_pembiasaan' => $a['contoh_pembiasaan'], 'fokus_pengamatan' => $a['fokus'], 'ikon' => $a['ikon'],
                'warna' => $a['warna'], 'urutan' => $i + 1, 'aktif' => true,
            ])->save();
        }
    }
}
