<?php

namespace Tests\Feature;

use App\Models\BabModul;
use App\Models\BerkasMedia;
use App\Models\VersiDokumen;
use App\Support\PembuatPdfSederhana;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Dua proses PHP terpisah mengaktifkan dua versi berbeda pada lingkup yang sama secara
 * bersamaan. Hasil wajib: tepat satu versi aktif, versi lain terarsip/draf, berkas ada.
 */
class AktivasiSerentakTest extends TestCase
{
    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_aktivasi_serentak_tidak_menghasilkan_dua_versi_aktif(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        DB::table('bab_modul')->insert(['nomor_bab' => 1, 'nomor_romawi' => 'I', 'judul' => 'Uji', 'slug' => 'uji', 'urutan' => 1, 'status' => 'terbit']);
        $bab = BabModul::query()->firstOrFail();
        $versi = [];
        foreach (['1.0', '1.1'] as $n) {
            $jalur = 'media/pdf/uji-'.Str::random(6).'.pdf';
            Storage::disk('local')->put($jalur, PembuatPdfSederhana::buat('Uji '.$n, [['isi']]));
            $m = new BerkasMedia;
            $m->forceFill(['uuid' => (string) Str::uuid(), 'judul' => 'Uji', 'jenis_media' => 'pdf', 'lokasi_penyimpanan' => 'local', 'jalur_berkas' => $jalur, 'tipe_mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran_bita' => 100, 'jumlah_halaman' => 1])->save();
            $v = new VersiDokumen;
            $v->forceFill(['jenis_dokumen' => 'bab', 'bab_id' => $bab->id, 'berkas_id' => $m->id, 'nomor_versi' => $n, 'judul_dokumen' => 'Uji '.$n, 'status' => 'draf', 'versi_aktif' => false])->save();
            $versi[] = $v;
        }

        $env = ['APP_ENV' => 'testing', 'DB_CONNECTION' => (string) config('database.default'), 'DB_DATABASE' => 'e_model_ping_testing', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'APP_URL' => 'http://ping.test'];
        for ($ulang = 0; $ulang < 3; $ulang++) {
            $hasil = Process::concurrently(function ($pool) use ($versi, $env) {
                foreach ($versi as $v) {
                    $pool->path(base_path())->env($env)->command([PHP_BINARY, 'artisan', 'ping:aktifkan-dokumen', (string) $v->id]);
                }
            });
            foreach ($hasil as $h) {
                $this->assertContains($h->exitCode(), [0, 1], $h->errorOutput());
            }
            $this->assertSame(1, VersiDokumen::query()->where('bab_id', $bab->id)->where('versi_aktif', true)->count());
            $aktif = VersiDokumen::query()->where('versi_aktif', true)->with('berkas')->firstOrFail();
            $this->assertSame('terbit', $aktif->status->value);
            Storage::disk('local')->assertExists($aktif->berkas->jalur_berkas);
        }

        foreach (BerkasMedia::query()->get() as $m) {
            Storage::disk('local')->delete($m->jalur_berkas);
        }
        Artisan::call('migrate:fresh', ['--force' => true]);
    }
}
