<?php

namespace App\Console\Commands;

use Database\Seeders\PenggunaSeeder;
use Illuminate\Console\Command;

/** Bootstrap akun admin (aman diulang: tidak membuat duplikat dan tidak mengganti sandi). */
class AdminAwal extends Command
{
    protected $signature = 'ping:admin-awal';

    protected $description = 'Buat akun admin awal dari ADMIN_AWAL_SUREL/ADMIN_AWAL_SANDI bila belum ada akun';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => PenggunaSeeder::class, '--force' => true]);

        return self::SUCCESS;
    }
}
