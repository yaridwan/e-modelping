<?php

namespace Tests\Feature;

use App\Console\Commands\HapusDemo;
use App\Models\KunjunganHalaman;
use App\Models\LogAktivitas;
use App\Models\PengalihanUrl;
use App\Models\PesanKontak;
use Database\Seeders\DataDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** ping:hapus-demo: hapus data demo saja (juga di produksi), data nyata tetap utuh. */
class HapusDemoTest extends TestCase
{
    use RefreshDatabase;

    private function siapkan(): PesanKontak
    {
        $this->seedDasar();
        (new DataDemoSeeder)->run();

        return PesanKontak::query()->forceCreate(['nama_pengirim' => 'Nyata', 'surel_pengirim' => 'nyata@contoh.test', 'subjek' => 'Pesan nyata', 'isi_pesan' => 'Isi pesan nyata', 'persetujuan_privasi' => true, 'status' => 'baru', 'data_contoh' => false]);
    }

    public function test_menghapus_data_demo_di_produksi_tanpa_menyentuh_data_nyata(): void
    {
        $nyata = $this->siapkan();
        $kunjunganNyata = KunjunganHalaman::query()->where('data_contoh', false)->count();
        $this->assertGreaterThan(0, array_sum(HapusDemo::hitung()));

        $this->app['env'] = 'production';
        $this->artisan('ping:hapus-demo', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, array_sum(HapusDemo::hitung()));
        $this->assertNotNull($nyata->fresh());
        $this->assertSame($kunjunganNyata, KunjunganHalaman::query()->where('data_contoh', false)->count());
        $this->assertFalse(PengalihanUrl::query()->where('jalur_lama', DataDemoSeeder::PENANDA_PENGALIHAN)->exists());
        $this->assertTrue(LogAktivitas::query()->where('aksi', 'hapus_demo')->where('data_contoh', false)->exists());

        // Dijalankan ulang: tidak ada yang dihapus, tetap sukses.
        $this->artisan('ping:hapus-demo', ['--force' => true])->expectsOutput('Tidak ada data demo. Tidak ada yang dihapus.')->assertSuccessful();
    }

    public function test_tanpa_konfirmasi_tidak_menghapus_apa_pun(): void
    {
        $this->siapkan();
        $total = array_sum(HapusDemo::hitung());

        $this->artisan('ping:hapus-demo')
            ->expectsConfirmation("Hapus permanen {$total} baris data demo? Data nyata tidak disentuh.", 'no')
            ->assertFailed();

        $this->assertSame($total, array_sum(HapusDemo::hitung()));
    }
}
