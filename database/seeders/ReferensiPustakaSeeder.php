<?php

namespace Database\Seeders;

use App\Models\ReferensiPustaka;
use Illuminate\Database\Seeder;

/**
 * Referensi persis seperti tercantum pada daftar pustaka PDF sumber. DOI/URL hanya yang
 * tertulis di sumber; status `tercantum_di_sumber` (belum diverifikasi independen).
 * Ketidakselarasan tahun sitasi dicatat pada catatan_editorial, tidak diperbaiki dengan menebak.
 */
class ReferensiPustakaSeeder extends Seeder
{
    public function run(): void
    {
        if (ReferensiPustaka::query()->exists()) {
            return;
        }
        foreach (MateriSumber::data()['referensi'] as $i => $r) {
            ReferensiPustaka::query()->create([
                ...$r,
                'status_verifikasi' => $r['catatan_editorial'] ? 'perlu_koreksi' : 'tercantum_di_sumber',
                'urutan' => $i + 1,
                'aktif' => true,
            ]);
        }
    }
}
