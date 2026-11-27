<?php

namespace App\Console\Commands;

use Database\Seeders\DataDemoSeeder;
use Illuminate\Console\Command;

/** DESTRUKTIF: menghapus semua baris data_contoh lalu membuat ulang data demo. Ditolak di produksi. */
class ResetDemo extends Command
{
    protected $signature = 'ping:reset-demo {--force : Lewati konfirmasi}';

    protected $description = '[DESTRUKTIF] Hapus seluruh data demonstrasi (data_contoh) lalu buat ulang';

    public function handle(): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('Reset demo ditolak di produksi.');

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm('Hapus seluruh data demonstrasi lalu buat ulang? Data nyata tidak disentuh.')) {
            return self::FAILURE;
        }
        HapusDemo::hapus();
        $this->call('db:seed', ['--class' => DataDemoSeeder::class, '--force' => true]);

        return self::SUCCESS;
    }
}
