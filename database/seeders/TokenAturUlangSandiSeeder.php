<?php

namespace Database\Seeders;

use App\Models\TokenAturUlangSandi;
use Illuminate\Database\Seeder;

/**
 * Seeder TIDAK membuat token reset aktif. Skenario token hanya ada di pengujian
 * (database terisolasi). Seeder ini memastikan tidak ada token aktif yang tertinggal.
 */
class TokenAturUlangSandiSeeder extends Seeder
{
    public function run(): void
    {
        $aktif = TokenAturUlangSandi::query()->whereNull('digunakan_pada')->where('kedaluwarsa_pada', '>', now())->count();
        $this->command?->info("token_atur_ulang_sandi: tidak ada data yang dibuat ({$aktif} token aktif dari permintaan nyata).");
    }
}
