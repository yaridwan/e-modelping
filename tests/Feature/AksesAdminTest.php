<?php

namespace Tests\Feature;

use App\Models\BabModul;
use App\Models\BerkasMedia;
use App\Models\FasePing;
use App\Models\HalamanInformasi;
use App\Models\SubbabModul;
use App\Models\VersiDokumen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AksesAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_semua_rute_admin_menolak_tamu(): void
    {
        $this->seedDasar();
        $diuji = 0;
        foreach (Route::getRoutes() as $rute) {
            if (! str_starts_with($rute->getName() ?? '', 'admin.')) {
                continue;
            }
            $uri = preg_replace('/\{[^}]+\}/', '1', str_replace('{tab}', 'identitas', $rute->uri()));
            foreach (array_diff($rute->methods(), ['HEAD']) as $metode) {
                $respons = $this->call($metode, '/'.$uri);
                $this->assertTrue(in_array($respons->getStatusCode(), [302, 419], true), "{$metode} /{$uri} harus menolak tamu, didapat {$respons->getStatusCode()}");
                if ($respons->getStatusCode() === 302) {
                    $this->assertStringEndsWith('/admin/masuk', $respons->headers->get('Location'));
                }
                $diuji++;
            }
        }
        $this->assertGreaterThan(60, $diuji);
    }

    public function test_admin_dapat_membuka_semua_halaman_daftar(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $bab = BabModul::query()->orderBy('id')->value('id');
        $fase = FasePing::query()->orderBy('id')->value('id');
        $subbab = SubbabModul::query()->where('bab_id', $bab)->value('id');
        $versi = VersiDokumen::query()->value('id');
        $media = BerkasMedia::query()->value('id');
        $halaman = HalamanInformasi::query()->value('id');
        foreach (["/admin/bab/{$bab}/subbab/{$subbab}/edit", "/admin/bab/{$bab}/subbab/buat", "/admin/dokumen/{$versi}", "/admin/media/{$media}", "/admin/halaman/{$halaman}/edit", "/admin/bab/{$bab}/edit", "/admin/bab/{$bab}/pratinjau", "/admin/fase/{$fase}/edit"] as $url) {
            $r = $this->actingAs($admin)->get($url);
            $this->assertSame(200, $r->status(), "{$url} menghasilkan {$r->status()}");
        }
        $this->actingAs($admin)->get("/admin/dokumen/{$versi}/pratinjau")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        foreach (['/admin', '/admin/bab', '/admin/dokumen', '/admin/dokumen/unggah', '/admin/fase', '/admin/karakter', '/admin/beranda', '/admin/halaman', '/admin/halaman/buat', '/admin/media', '/admin/galeri', '/admin/galeri/buat', '/admin/faq', '/admin/referensi', '/admin/menu', '/admin/pengalihan', '/admin/pesan', '/admin/statistik', '/admin/log-aktivitas', '/admin/pengaturan', '/admin/profil', '/admin/keamanan'] as $url) {
            $r = $this->actingAs($admin)->get($url);
            $this->assertSame(200, $r->status(), "{$url} menghasilkan {$r->status()}");
            $r->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    public function test_props_publik_tidak_membocorkan_data_privat_admin(): void
    {
        $this->seedDasar();
        $admin = $this->admin(['nomor_telepon' => '081234567890', 'tampilkan_profil_publik' => true, 'jabatan' => 'Guru PAUD']);
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString($admin->surel, $html);
        $this->assertStringNotContainsString('081234567890', $html);
        $this->assertStringContainsString('Guru PAUD', $html);

        // Saat admin masuk dan membuka halaman publik, surel akun tetap tidak dibagikan.
        $html = $this->actingAs($admin)->get('/modul')->getContent();
        $this->assertStringNotContainsString($admin->surel, $html);
        $this->assertStringNotContainsString('kata_sandi', $html);
    }

    public function test_pengguna_nonaktif_dikeluarkan(): void
    {
        $this->seedDasar();
        $admin = $this->admin(['status' => 'nonaktif']);
        $this->actingAs($admin)->get('/admin')->assertRedirect('/admin/masuk');
        $this->assertGuest();
    }
}
