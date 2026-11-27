<?php

namespace Tests\Feature;

use App\Models\BabModul;
use App\Models\PengalihanUrl;
use App\Models\SubbabModul;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublikasiKontenTest extends TestCase
{
    use RefreshDatabase;

    public function test_semua_bab_terbit_dapat_diakses_dengan_isi_di_html_awal(): void
    {
        $this->seedDasar();
        foreach (BabModul::query()->get() as $bab) {
            $r = $this->get('/modul/'.$bab->slug)->assertOk();
            $r->assertSee('<title>Bab '.$bab->nomor_romawi, false);
            $r->assertSee('rel="canonical" href="http://ping.test/modul/'.$bab->slug.'"', false);
        }
        $this->get('/modul/sintaks-model')->assertSee('Pre-Class Greeting merupakan fase awal', false);
    }

    public function test_subbab_draf_tersembunyi_dari_publik_pencarian_dan_sitemap(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $sub = SubbabModul::query()->where('kode_subbab', 'VI-C')->firstOrFail();
        $bab = $sub->bab;

        $this->actingAs($admin)->post("/admin/bab/{$bab->id}/subbab/{$sub->id}/status", ['status' => 'draf'])->assertRedirect();
        auth()->logout();

        $halaman = $this->get('/modul/'.$bab->slug)->assertOk()->getContent();
        $this->assertStringNotContainsString('"slug":"karakter-kontrol-diri"', $halaman);
        $cari = $this->get('/cari?q='.urlencode('mengelola respons emosional'))->assertOk();
        $this->assertStringNotContainsString('Karakter Kontrol Diri', json_encode($cari->viewData('page')['props']['hasil']));

        // Admin tetap dapat mempratinjau.
        $this->actingAs($admin)->get("/admin/bab/{$bab->id}/pratinjau")->assertOk()->assertSee('karakter-kontrol-diri', false);
    }

    public function test_arsip_bab_menghapus_dari_publik_sitemap_navigasi_dan_dokumen(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $bab = BabModul::query()->where('nomor_bab', 5)->firstOrFail();
        $versi = $bab->versiAktif;
        $this->get("/dokumen/{$versi->id}/berkas")->assertOk();

        $this->actingAs($admin)->post("/admin/bab/{$bab->id}/status", ['status' => 'arsip']);
        auth()->logout();

        $this->get('/modul/peran-guru')->assertNotFound();
        $this->assertStringNotContainsString('/modul/peran-guru', $this->get('/sitemap.xml')->getContent());
        $this->get("/dokumen/{$versi->id}/berkas")->assertNotFound();
        $this->get("/dokumen/{$versi->id}/baca")->assertNotFound();
        $daftar = $this->get('/modul')->viewData('page')['props']['bab'];
        $this->assertNotContains('peran-guru', array_column($daftar, 'slug'));
        $this->get('/modul/pendahuluan')->assertOk();
    }

    public function test_ubah_slug_membuat_pengalihan_301_tanpa_rantai(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $bab = BabModul::query()->where('nomor_bab', 4)->firstOrFail();
        $data = ['judul' => $bab->judul, 'ringkasan' => $bab->ringkasan, 'tujuan_pembelajaran' => $bab->tujuan_pembelajaran, 'poin_penting' => $bab->poin_penting];

        $this->actingAs($admin)->put("/admin/bab/{$bab->id}", [...$data, 'slug' => 'sintaks-ping'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->put("/admin/bab/{$bab->id}", [...$data, 'slug' => 'sintaks-model-ping'])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/modul/sintaks-model')->assertStatus(301)->assertRedirect('/modul/sintaks-model-ping');
        $this->get('/modul/sintaks-ping')->assertStatus(301)->assertRedirect('/modul/sintaks-model-ping');
        $this->get('/modul/sintaks-model-ping')->assertOk();
        $this->assertSame(0, PengalihanUrl::query()->where('jalur_lama', '/modul/sintaks-model-ping')->count());
    }

    public function test_urutan_subbab_atomik_tanpa_bentrok_indeks_unik(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $bab = BabModul::query()->where('nomor_bab', 2)->firstOrFail();
        $ids = $bab->subbab()->pluck('id')->all();
        $baru = array_reverse($ids);
        $this->actingAs($admin)->post("/admin/bab/{$bab->id}/subbab/urutan", ['id' => $baru])->assertSessionHasNoErrors();
        $this->assertSame($baru, $bab->subbab()->pluck('id')->all());
        $this->assertSame(range(1, count($ids)), $bab->subbab()->pluck('urutan')->all());
        // Daftar tidak lengkap ditolak.
        $this->actingAs($admin)->post("/admin/bab/{$bab->id}/subbab/urutan", ['id' => array_slice($ids, 1)])->assertSessionHasErrors('urutan');
    }

    public function test_struktur_bab_tidak_dapat_dihapus_atau_ditambah(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $this->actingAs($admin)->delete('/admin/bab/1')->assertStatus(405);
        $this->actingAs($admin)->post('/admin/bab', [])->assertStatus(405);
        $this->assertSame(9, BabModul::query()->count());
    }

    public function test_404_benar_untuk_konten_tidak_ada(): void
    {
        $this->seedDasar();
        $r = $this->get('/modul/tidak-ada')->assertNotFound();
        $this->assertSame('galat', $r->viewData('page')['component']);
        $this->assertSame(404, $r->viewData('page')['props']['status']);
        $this->get('/halaman-acak-tidak-ada')->assertNotFound();
        $this->get('/fase/tidak-ada')->assertNotFound();
    }
}
