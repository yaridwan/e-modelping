<?php

namespace App\Console\Commands;

use App\Layanan\LayananDokumen;
use App\Models\VersiDokumen;
use Illuminate\Console\Command;
use Throwable;

/** Aktifkan versi dokumen dari CLI (juga dipakai uji aktivasi serentak). */
class AktifkanDokumen extends Command
{
    protected $signature = 'ping:aktifkan-dokumen {versi : ID versi dokumen}';

    protected $description = 'Terbitkan dan aktifkan satu versi dokumen (versi aktif sebelumnya diarsipkan)';

    public function handle(LayananDokumen $dokumen): int
    {
        try {
            $v = $dokumen->aktifkan(VersiDokumen::query()->findOrFail((int) $this->argument('versi')));
            $this->info("Versi {$v->id} aktif.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
