<?php

namespace Tests;

use App\Layanan\LayananCaptcha;
use App\Models\Pengguna;
use Database\Seeders\DataDasarSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    /** Muat data dasar (tanpa demo) ke database uji. */
    protected function seedDasar(): void
    {
        Storage::fake('local');
        $this->seed(DataDasarSeeder::class);
    }

    protected function admin(array $atribut = []): Pengguna
    {
        $p = Pengguna::query()->first();
        if (! $p) {
            $p = new Pengguna;
            $p->forceFill(array_merge([
                'nama_lengkap' => 'Admin Uji',
                'surel' => 'admin@emodelping.test',
                'kata_sandi' => Hash::make('SandiUji12345'),
                'status' => 'aktif',
            ], $atribut))->save();
        } elseif ($atribut) {
            $p->forceFill($atribut)->save();
        }

        return $p;
    }

    /** Ambil captcha aktif dari sesi uji: jawaban dihitung dari soal (bukan dari klien). */
    protected function captchaBaru(): array
    {
        $soal = app(LayananCaptcha::class)->buat();
        [$a, $op, $b] = explode(' ', $soal['soal']);
        $jawaban = $op === '+' ? (int) $a + (int) $b : (int) $a - (int) $b;

        return ['id' => $soal['id'], 'jawaban' => (string) $jawaban];
    }
}
