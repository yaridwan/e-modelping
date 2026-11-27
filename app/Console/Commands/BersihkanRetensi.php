<?php

namespace App\Console\Commands;

use App\Layanan\LayananStatistik;
use Illuminate\Console\Command;

class BersihkanRetensi extends Command
{
    protected $signature = 'ping:bersihkan-retensi';

    protected $description = 'Hapus statistik, pesan arsip/ditindaklanjuti, dan token reset yang melewati masa retensi';

    public function handle(LayananStatistik $statistik): int
    {
        foreach ($statistik->bersihkanRetensi() as $tabel => $jumlah) {
            $this->line("{$tabel}: {$jumlah} baris dihapus");
        }

        return self::SUCCESS;
    }
}
