<?php

namespace Tests\Feature;

use App\Models\BerkasMedia;
use App\Models\GaleriKegiatan;
use App\Models\MenuNavigasi;
use App\Models\Pengguna;
use App\Models\VersiDokumen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SeoIdentitasTest extends TestCase
{
    use RefreshDatabase;

    public function test_html_awal_memuat_judul_metadata_canonical_jsonld(): void
    {
        $this->seedDasar();
        $r = $this->get('/', ['Host' => 'penyerang.test'])->assertOk();
        $html = $r->getContent();
        $this->assertStringContainsString('<html lang="id"', $html);
        $this->assertStringContainsString('<title>E-Model PING — Model Pembiasaan Karakter Anak Usia Dini</title>', $html);
        $this->assertStringContainsString('<link data-inertia="canonical" rel="canonical" href="http://ping.test/">', $html);
        $this->assertStringNotContainsString('penyerang.test', $html);
        $this->assertStringContainsString('property="og:image"', $html);
        $this->assertStringContainsString('"@type":"WebSite"', $html);
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString('name="robots" content="index,follow"', $html);
        // Isi bab tersedia dalam respons awal (prop halaman), tidak bergantung pada PDF.
        $bab = $this->get('/modul/indikator-karakter')->getContent();
        $this->assertStringContainsString('"@type":"LearningResource"', $bab);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $bab);
        $this->assertStringContainsString('Disiplin dalam Model Pembiasaan PING diamati', $bab);
    }

    public function test_noindex_admin_pencarian_dan_halaman_dokumen(): void
    {
        $this->seedDasar();
        $this->get('/cari?q=disiplin')->assertOk()->assertSee('name="robots" content="noindex,follow"', false);
        $this->get('/admin/masuk')->assertSee('content="noindex,nofollow"', false)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $versi = VersiDokumen::query()->where('jenis_dokumen', 'modul_lengkap')->firstOrFail();
        $this->get("/dokumen/{$versi->id}/baca")->assertOk()->assertSee('content="noindex,follow"', false);
    }

    public function test_sitemap_dan_robots(): void
    {
        $this->seedDasar();
        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $this->assertStringContainsString('<loc>http://ping.test/modul/sintaks-model</loc>', $xml);
        $this->assertStringContainsString('<loc>http://ping.test/fase/pre-class-greeting</loc>', $xml);
        $this->assertStringContainsString('<lastmod>', $xml);
        foreach (['/admin', '/cari', '/dokumen/', 'atur-ulang', '/galeri'] as $terlarang) {
            $this->assertStringNotContainsString('http://ping.test'.$terlarang, $xml);
        }
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function test_perubahan_identitas_berlaku_di_publik_login_dashboard_dan_metadata(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $logo = UploadedFile::fake()->image('logo.png', 256, 256);
        $this->actingAs($admin)->post('/admin/pengaturan/identitas', [
            'nama_aplikasi' => 'PING Pembiasaan', 'nama_singkat' => 'PINGku', 'tagline' => 'Tagline baru', 'zona_waktu' => 'Asia/Jakarta', 'logo_terang' => $logo,
        ])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/pengaturan/tampilan', ['warna_utama' => '#9D174D', 'warna_pendamping' => '#DB2777', 'tema_bawaan' => 'gelap', 'teks_footer' => 'Footer baru', 'tahun_hak_cipta_awal' => 2026])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/pengaturan/tampilan', ['warna_utama' => 'red;}body{display:none', 'warna_pendamping' => '#DB2777', 'tema_bawaan' => 'gelap'])->assertSessionHasErrors('warna_utama');
        auth()->logout();

        $beranda = $this->get('/');
        $this->assertSame('PING Pembiasaan', $beranda->viewData('page')['props']['situs']['nama_aplikasi']);
        $this->assertStringContainsString('--merek: #9D174D', $beranda->getContent());
        $this->assertStringContainsString('data-tema="dark"', $beranda->getContent());
        $logoUrl = $beranda->viewData('page')['props']['situs']['logo_terang'];
        $this->assertNotNull($logoUrl);
        $this->get($logoUrl)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('| PING Pembiasaan</title>', $this->get('/modul')->getContent());
        $this->assertSame('PING Pembiasaan', $this->get('/admin/masuk')->viewData('page')['props']['situs']['nama_aplikasi']);
        $this->assertSame('PING Pembiasaan', $this->actingAs($admin)->get('/admin')->viewData('page')['props']['situs']['nama_aplikasi']);
    }

    public function test_profil_publik_hanya_data_yang_diizinkan_dan_foto(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/profil', [
            'nama_lengkap' => 'Tita Sapriyanti', 'nomor_telepon' => '081299990000', 'jabatan' => 'Penyusun', 'biografi' => 'Biografi publik penyusun.',
            'surel_publik' => 'publik@contoh.test', 'tampilkan_profil_publik' => true, 'tampilkan_foto_publik' => false, 'tampilkan_kontak_publik' => false, 'tema_pilihan' => 'sistem',
        ])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/profil/foto', ['foto' => UploadedFile::fake()->image('foto.jpg', 400, 400)])->assertSessionHasNoErrors();
        $foto = Pengguna::query()->firstOrFail()->fotoProfil;
        auth()->logout();

        $html = $this->get('/tentang')->assertOk()->getContent();
        $this->assertStringContainsString('Biografi publik penyusun.', $html);
        $this->assertStringNotContainsString('081299990000', $html);
        $this->assertStringNotContainsString('publik@contoh.test', $html);
        $this->assertStringNotContainsString($admin->surel, $html);
        // Foto belum diizinkan tampil publik: URL media ditolak untuk tamu.
        $this->get($foto->urlPublik())->assertNotFound();

        $this->actingAs($admin)->put('/admin/profil', ['nama_lengkap' => 'Tita Sapriyanti', 'tampilkan_profil_publik' => true, 'tampilkan_foto_publik' => true, 'tampilkan_kontak_publik' => true, 'surel_publik' => 'publik@contoh.test', 'tema_pilihan' => 'sistem'])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get($foto->urlPublik())->assertOk();
        $this->assertStringContainsString('publik@contoh.test', $this->get('/tentang')->getContent());
    }

    public function test_media_terpakai_tidak_dapat_dihapus_dan_gambar_draf_privat(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/media', ['jenis_media' => 'gambar', 'berkas' => UploadedFile::fake()->image('kegiatan.jpg', 800, 600), 'teks_alternatif' => 'Ilustrasi kegiatan'])->assertSessionHasNoErrors();
        $media = BerkasMedia::query()->where('jenis_media', 'gambar')->latest('id')->firstOrFail();
        $this->actingAs($admin)->post('/admin/media', ['jenis_media' => 'gambar', 'berkas' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'), 'teks_alternatif' => 'x'])->assertSessionHasErrors('berkas');

        $this->actingAs($admin)->post('/admin/galeri', ['judul' => 'Kegiatan', 'slug' => 'kegiatan', 'media_id' => $media->id, 'teks_alternatif' => 'Ilustrasi'])->assertSessionHasNoErrors();
        $galeri = GaleriKegiatan::query()->firstOrFail();
        auth()->logout();
        $this->get($media->urlPublik())->assertNotFound();

        $this->actingAs($admin)->post("/admin/galeri/{$galeri->id}/status", ['status' => 'terbit'])->assertSessionHas('galat');
        $galeri->forceFill(['dasar_izin_publikasi' => 'Ilustrasi buatan sendiri, tanpa anak.'])->save();
        $this->actingAs($admin)->post("/admin/galeri/{$galeri->id}/status", ['status' => 'terbit'])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get($media->urlPublik())->assertOk();

        $this->actingAs($admin)->delete("/admin/media/{$media->id}")->assertSessionHasErrors('media');
        $this->assertNotNull($media->fresh());
    }

    public function test_menu_menolak_protokol_berbahaya_dan_siklus(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/menu', ['lokasi' => 'utama', 'label' => 'Jahat', 'jenis_tautan' => 'eksternal', 'url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');
        $this->actingAs($admin)->post('/admin/menu', ['lokasi' => 'utama', 'label' => 'Jahat', 'jenis_tautan' => 'internal', 'url' => '//jahat.test'])->assertSessionHasErrors('url');
        $induk = MenuNavigasi::query()->where('lokasi', 'utama')->firstOrFail();
        $this->actingAs($admin)->put("/admin/menu/{$induk->id}", ['lokasi' => 'utama', 'label' => $induk->label, 'jenis_tautan' => 'internal', 'url' => '/', 'induk_id' => $induk->id])->assertSessionHasErrors('induk_id');
    }
}
