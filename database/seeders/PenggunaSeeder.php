<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Bootstrap akun admin. Hanya membuat akun bila belum ada; TIDAK pernah mengganti sandi akun
 * yang sudah ada. Sandi berasal dari ADMIN_AWAL_SANDI; di lokal bila kosong dibuat acak dan
 * dicetak sekali ke CLI (tidak ditulis ke log). Di produksi sandi wajib dari environment.
 */
class PenggunaSeeder extends Seeder
{
    public function run(): void
    {
        $surel = Str::lower((string) config('ping.admin_awal.surel'));
        if (Pengguna::query()->exists()) {
            $this->command?->info('Akun admin sudah ada; tidak ada perubahan (sandi tidak diubah).');

            return;
        }

        $sandi = (string) config('ping.admin_awal.sandi');
        $acak = false;
        if ($sandi === '') {
            if (app()->isProduction()) {
                $this->command?->error('ADMIN_AWAL_SANDI belum diisi. Akun admin produksi tidak dibuat. Isi sandi kuat (min. 12 karakter) lalu jalankan ulang `php artisan ping:admin-awal`.');

                return;
            }
            $sandi = Str::password(16, symbols: false);
            $acak = true;
        } elseif (app()->isProduction() && mb_strlen($sandi) < 12) {
            $this->command?->error('ADMIN_AWAL_SANDI terlalu pendek untuk produksi (min. 12 karakter). Akun tidak dibuat.');

            return;
        }

        $pengguna = new Pengguna;
        $pengguna->forceFill([
            'nama_lengkap' => (string) config('ping.admin_awal.nama'),
            'nama_panggilan' => 'Admin',
            'surel' => $surel,
            'kata_sandi' => Hash::make($sandi),
            'surel_diverifikasi_pada' => now(),
            'tema_pilihan' => 'sistem',
            'status' => 'aktif',
            'tampilkan_profil_publik' => false,
            'wajib_ganti_sandi' => $acak,
            'sandi_diubah_pada' => now(),
        ])->save();

        if ($acak && $this->command) {
            $this->command->warn('Akun admin lokal dibuat: '.$surel);
            $this->command->warn('Sandi sementara (ditampilkan SEKALI, simpan sekarang): '.$sandi);
            $this->command->warn('Ganti sandi melalui menu Keamanan setelah masuk.');
        } else {
            $this->command?->info('Akun admin dibuat: '.$surel.' (sandi dari ADMIN_AWAL_SANDI). Hapus nilai tersebut dari .env.');
        }
    }
}
