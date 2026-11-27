<?php

namespace Tests\Feature;

use App\Models\AspekKarakter;
use App\Models\BabModul;
use App\Models\FasePing;
use App\Models\IndikatorKarakter;
use App\Models\KunjunganHalaman;
use App\Models\LangkahFase;
use App\Models\PengaturanWebsite;
use App\Models\Pengguna;
use App\Models\PesanKontak;
use App\Models\SubbabModul;
use App\Models\TokenAturUlangSandi;
use App\Models\VersiDokumen;
use Database\Seeders\DataDasarSeeder;
use Database\Seeders\DataDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_dasar_lengkap_sesuai_struktur_ping(): void
    {
        $this->seedDasar();

        $this->assertSame(9, BabModul::query()->count());
        $this->assertSame(range(1, 9), BabModul::query()->orderBy('nomor_bab')->pluck('nomor_bab')->all());
        $this->assertSame(9, BabModul::query()->terbit()->count());
        $this->assertGreaterThanOrEqual(30, SubbabModul::query()->count());
        $this->assertSame(3, FasePing::query()->count());
        $this->assertSame([6, 6, 5], FasePing::query()->orderBy('urutan')->withCount('langkah')->pluck('langkah_count')->all());
        $this->assertSame(17, LangkahFase::query()->count());
        $this->assertSame(4, AspekKarakter::query()->count());
        $this->assertSame(16, IndikatorKarakter::query()->count());
        $this->assertSame(range(1, 16), IndikatorKarakter::query()->orderBy('nomor_indikator')->pluck('nomor_indikator')->all());
        $this->assertSame(1, PengaturanWebsite::query()->count());
        $this->assertSame(1, Pengguna::query()->count());
        $this->assertSame(0, TokenAturUlangSandi::query()->count());
        $this->assertSame(0, KunjunganHalaman::query()->count());

        // Dokumen: 9 bab + modul lengkap, berkas fisik ada, satu versi aktif per lingkup.
        $this->assertSame(10, VersiDokumen::query()->where('versi_aktif', true)->count());
        foreach (VersiDokumen::query()->with('berkas')->get() as $v) {
            Storage::disk('local')->assertExists($v->berkas->jalur_berkas);
            $this->assertGreaterThan(0, $v->berkas->jumlah_halaman);
        }

        // Materi HTML bermakna (bukan sekadar iframe PDF) dan provenance halaman tercatat.
        $latar = SubbabModul::query()->where('kode_subbab', 'I-A')->firstOrFail();
        $this->assertStringContainsString('Pendidikan karakter merupakan salah satu fondasi utama', $latar->isi);
        $this->assertSame([9, 10], $latar->metadata_sumber['halaman_pdf']);
        $this->assertStringNotContainsString('lorem', strtolower(SubbabModul::query()->pluck('isi')->implode(' ')));
    }

    public function test_seed_ulang_tidak_menimpa_suntingan_pemilik_atau_sandi(): void
    {
        $this->seedDasar();
        $admin = Pengguna::query()->firstOrFail();
        $hashLama = $admin->kata_sandi;
        BabModul::query()->where('nomor_bab', 1)->update(['judul' => 'Judul suntingan pemilik']);
        PengaturanWebsite::query()->update(['nama_aplikasi' => 'Nama Kustom']);

        $this->seed(DataDasarSeeder::class);

        $this->assertSame('Judul suntingan pemilik', BabModul::query()->where('nomor_bab', 1)->value('judul'));
        $this->assertSame('Nama Kustom', PengaturanWebsite::query()->value('nama_aplikasi'));
        $this->assertSame($hashLama, Pengguna::query()->value('kata_sandi'));
        $this->assertSame(1, Pengguna::query()->count());
        $this->assertSame(9, BabModul::query()->count());
        $this->assertSame(10, VersiDokumen::query()->count());
        $this->assertTrue(Hash::check('SandiUji12345', $hashLama));
    }

    public function test_data_demo_terpisah_dan_ditolak_di_produksi(): void
    {
        $this->seedDasar();
        $this->seed(DataDemoSeeder::class);
        $this->assertGreaterThan(100, KunjunganHalaman::query()->where('data_contoh', true)->count());
        $this->assertSame(0, KunjunganHalaman::query()->where('data_contoh', false)->count());
        $this->assertSame(PesanKontak::query()->count(), PesanKontak::query()->where('data_contoh', true)->count());

        $this->app['env'] = 'production';
        $this->expectException(\RuntimeException::class);
        (new DataDemoSeeder)->run();
    }
}
