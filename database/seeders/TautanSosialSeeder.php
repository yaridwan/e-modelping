<?php

namespace Database\Seeders;

use App\Models\TautanSosial;
use Illuminate\Database\Seeder;

/**
 * Akun sosial pemilik tidak diketahui dari sumber: entri dibuat NONAKTIF tanpa URL sebagai
 * slot yang dapat diisi pemilik. Tidak ada akun orang lain yang dicantumkan.
 */
class TautanSosialSeeder extends Seeder
{
    public function run(): void
    {
        if (TautanSosial::query()->exists()) {
            return;
        }
        foreach ([['instagram', 'Instagram'], ['youtube', 'YouTube'], ['facebook', 'Facebook']] as $i => [$platform, $label]) {
            TautanSosial::query()->create(['nama_platform' => $platform, 'label' => $label, 'url' => null, 'ikon' => $platform, 'urutan' => $i + 1, 'aktif' => false]);
        }
    }
}
