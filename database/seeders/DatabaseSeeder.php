<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * `php artisan db:seed` hanya memuat DATA DASAR yang aman untuk produksi dan aman diulang
 * (tidak menimpa suntingan pemilik, sandi, branding, atau konten yang sudah dikelola).
 * Data demonstrasi dijalankan terpisah: `php artisan db:seed --class=DataDemoSeeder`
 * (ditolak di produksi).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DataDasarSeeder::class);
    }
}
