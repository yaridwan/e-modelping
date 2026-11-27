<?php

namespace App\Console\Commands;

use App\Layanan\LayananMedia;
use App\Models\BerkasMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Pemeriksaan konsistensi penyimpanan: rekaman berkas_media tanpa berkas fisik dan berkas
 * fisik di storage/app/private/media yang tidak dirujuk database (yatim).
 */
class PeriksaBerkas extends Command
{
    protected $signature = 'ping:periksa-berkas {--hapus-yatim : Hapus berkas fisik yang tidak dirujuk database}';

    protected $description = 'Periksa konsistensi berkas media privat (hilang/yatim)';

    public function handle(): int
    {
        $disk = Storage::disk(LayananMedia::DISK);
        $dirujuk = BerkasMedia::query()->whereNotNull('jalur_berkas')->pluck('jalur_berkas')->all();
        $hilang = array_values(array_filter($dirujuk, fn ($j) => ! $disk->exists($j)));
        $yatim = array_values(array_diff($disk->allFiles('media'), $dirujuk));

        $this->info('Berkas dirujuk: '.count($dirujuk).', hilang: '.count($hilang).', yatim: '.count($yatim));
        foreach ($hilang as $j) {
            $this->error("Hilang: {$j}");
        }
        if ($this->option('hapus-yatim')) {
            $disk->delete($yatim);
            $this->info(count($yatim).' berkas yatim dihapus.');
        } else {
            foreach (array_slice($yatim, 0, 20) as $j) {
                $this->line("Yatim: {$j}");
            }
        }

        return $hilang === [] ? self::SUCCESS : self::FAILURE;
    }
}
