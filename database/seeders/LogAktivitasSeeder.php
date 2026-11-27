<?php

namespace Database\Seeders;

use App\Models\LogAktivitas;
use Illuminate\Database\Seeder;

/** Mencatat satu entri audit bahwa data dasar dimuat (sekali saja). */
class LogAktivitasSeeder extends Seeder
{
    public function run(): void
    {
        if (LogAktivitas::query()->where('aksi', 'seed_data_dasar')->exists()) {
            return;
        }
        LogAktivitas::query()->create([
            'aksi' => 'seed_data_dasar', 'jenis_entitas' => 'sistem', 'ringkasan' => 'Data dasar dimuat oleh seeder',
            'data_contoh' => false, 'dibuat_pada' => now(),
        ]);
    }
}
