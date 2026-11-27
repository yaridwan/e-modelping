<?php

namespace Tests\Feature;

use App\Layanan\LayananPengaturan;
use App\Models\AktivitasDokumen;
use App\Models\BabModul;
use App\Models\PengaturanWebsite;
use App\Models\VersiDokumen;
use App\Support\PembuatPdfSederhana;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DokumenPdfTest extends TestCase
{
    use RefreshDatabase;

    private function pdfValid(string $nama = 'bab-iv-v2.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nama, PembuatPdfSederhana::buat('Uji Bab IV', [['Halaman satu'], ['Halaman dua']]));
    }

    private function unggah(UploadedFile $berkas, array $data = []): TestResponse
    {
        $bab = BabModul::query()->where('nomor_bab', 4)->firstOrFail();

        return $this->actingAs($this->admin())->post('/admin/dokumen', array_merge([
            'jenis_dokumen' => 'bab', 'bab_id' => $bab->id, 'nomor_versi' => '2.0', 'judul_dokumen' => 'Bab IV revisi', 'izinkan_unduh' => true, 'berkas' => $berkas,
        ], $data));
    }

    public function test_pdf_valid_diterima_sebagai_draf_privat(): void
    {
        $this->seedDasar();
        $this->unggah($this->pdfValid())->assertSessionHasNoErrors()->assertRedirect();
        $v = VersiDokumen::query()->where('nomor_versi', '2.0')->firstOrFail();
        $this->assertSame('draf', $v->status->value);
        $this->assertFalse($v->versi_aktif);
        $this->assertSame(2, $v->berkas->jumlah_halaman);
        $this->assertStringStartsWith('media/pdf/', $v->berkas->jalur_berkas);
        $this->assertStringNotContainsString('bab-iv-v2', $v->berkas->jalur_berkas);

        auth()->logout();
        $this->get("/dokumen/{$v->id}/berkas")->assertNotFound();
        $this->get("/dokumen/{$v->id}/unduh")->assertNotFound();
        $this->actingAs($this->admin())->get("/admin/dokumen/{$v->id}/pratinjau")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('no-store', $this->actingAs($this->admin())->get("/admin/dokumen/{$v->id}/pratinjau")->headers->get('Cache-Control'));
    }

    public function test_berkas_palsu_rusak_terenkripsi_dan_terlalu_besar_ditolak(): void
    {
        $this->seedDasar();
        $this->unggah(UploadedFile::fake()->createWithContent('palsu.pdf', "MZ\x90\x00 ini bukan pdf sama sekali, executable disamarkan"))->assertSessionHasErrors('berkas');
        $this->unggah(UploadedFile::fake()->createWithContent('teks.pdf', str_repeat('teks biasa ', 50)))->assertSessionHasErrors('berkas');
        $this->unggah(UploadedFile::fake()->createWithContent('rusak.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n"), ['nomor_versi' => '2.1'])->assertSessionHasErrors('berkas');
        $terenkripsi = str_replace('/Root 1 0 R', '/Root 1 0 R /Encrypt 9 0 R', PembuatPdfSederhana::buat('Rahasia', [['x']]));
        $this->unggah(UploadedFile::fake()->createWithContent('enkripsi.pdf', $terenkripsi), ['nomor_versi' => '2.2'])->assertSessionHasErrors('berkas');
        $js = str_replace('/Type /Catalog', '/Type /Catalog /OpenAction << /S /JavaScript /JS (app.alert(1)) >>', PembuatPdfSederhana::buat('JS', [['x']]));
        $this->unggah(UploadedFile::fake()->createWithContent('js.pdf', $js), ['nomor_versi' => '2.3'])->assertSessionHasErrors('berkas');

        PengaturanWebsite::query()->update(['batas_unggah_mb' => 1]);
        app(LayananPengaturan::class)->bersihkan();
        $besar = UploadedFile::fake()->createWithContent('besar.pdf', PembuatPdfSederhana::buat('Besar', [['x']]).str_repeat('%', 1_200_000));
        $this->unggah($besar, ['nomor_versi' => '2.4'])->assertSessionHasErrors('berkas');

        $this->assertSame(0, VersiDokumen::query()->where('nomor_versi', 'like', '2.%')->count());
    }

    public function test_aktivasi_mengarsipkan_versi_lama_dan_publik_hanya_versi_aktif(): void
    {
        $this->seedDasar();
        $bab = BabModul::query()->where('nomor_bab', 4)->firstOrFail();
        $lama = $bab->versiAktif;
        $this->unggah($this->pdfValid());
        $baru = VersiDokumen::query()->where('nomor_versi', '2.0')->firstOrFail();

        $this->actingAs($this->admin())->post("/admin/dokumen/{$baru->id}/aktifkan")->assertSessionHasNoErrors();
        auth()->logout();

        $this->assertTrue($baru->fresh()->versi_aktif);
        $this->assertSame('arsip', $lama->fresh()->status->value);
        $this->assertFalse($lama->fresh()->versi_aktif);
        $this->assertSame(1, VersiDokumen::query()->where('bab_id', $bab->id)->where('versi_aktif', true)->count());
        $this->get("/dokumen/{$lama->id}/berkas")->assertNotFound();
        $this->get("/dokumen/{$baru->id}/berkas")->assertOk();
        $this->get('/modul/sintaks-model')->assertSee('"nomor_versi":"2.0"', false);
    }

    public function test_header_aman_range_206_dan_deduplikasi_statistik(): void
    {
        $this->seedDasar();
        $v = BabModul::query()->where('nomor_bab', 1)->firstOrFail()->versiAktif;
        $ua = ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/140 Safari/537.36'];

        $r = $this->get("/dokumen/{$v->id}/berkas", $ua)->assertOk();
        $r->assertHeader('Content-Type', 'application/pdf');
        $r->assertHeader('X-Content-Type-Options', 'nosniff');
        $r->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $r->assertHeader('Accept-Ranges', 'bytes');
        $this->assertStringContainsString('inline', $r->headers->get('Content-Disposition'));
        $this->assertStringContainsString('e-model-ping-bab-1-v1.0.pdf', $r->headers->get('Content-Disposition'));

        $sebagian = $this->get("/dokumen/{$v->id}/berkas", $ua + ['Range' => 'bytes=1000-1999']);
        $sebagian->assertStatus(206);
        $sebagian->assertHeader('Content-Length', '1000');
        $this->assertStringStartsWith('bytes 1000-1999/', $sebagian->headers->get('Content-Range'));
        $this->get("/dokumen/{$v->id}/berkas", $ua + ['Range' => 'bytes=0-65535'])->assertStatus(206);
        $this->get("/dokumen/{$v->id}/berkas", $ua)->assertOk();

        $this->assertSame(1, AktivitasDokumen::query()->where('versi_dokumen_id', $v->id)->where('jenis_aktivitas', 'baca')->count());
    }

    public function test_izin_unduh_global_dan_per_dokumen(): void
    {
        $this->seedDasar();
        $v = BabModul::query()->where('nomor_bab', 2)->firstOrFail()->versiAktif;
        $r = $this->get("/dokumen/{$v->id}/unduh")->assertOk();
        $this->assertStringContainsString('attachment', $r->headers->get('Content-Disposition'));

        $v->forceFill(['izinkan_unduh' => false])->save();
        $this->get("/dokumen/{$v->id}/unduh")->assertForbidden();
        $this->get("/dokumen/{$v->id}/berkas")->assertOk();

        $v->forceFill(['izinkan_unduh' => true])->save();
        PengaturanWebsite::query()->update(['izinkan_unduh' => false]);
        app(LayananPengaturan::class)->bersihkan();
        $this->get("/dokumen/{$v->id}/unduh")->assertForbidden();
        $this->assertNull($this->get('/modul/landasan-teori')->viewData('page')['props']['dokumen']['url_unduh']);
    }

    public function test_bab_terbit_tanpa_pdf_tetap_dapat_dibaca(): void
    {
        $this->seedDasar();
        $bab = BabModul::query()->where('nomor_bab', 3)->firstOrFail();
        $bab->versiAktif->forceFill(['status' => 'arsip', 'versi_aktif' => false])->save();
        $r = $this->get('/modul/'.$bab->slug)->assertOk();
        $this->assertNull($r->viewData('page')['props']['dokumen']);
    }
}
