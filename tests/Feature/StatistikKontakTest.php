<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\KunjunganHalaman;
use App\Models\PesanKontak;
use Database\Seeders\DataDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class StatistikKontakTest extends TestCase
{
    use RefreshDatabase;

    private array $ua = ['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile Safari'];

    public function test_kunjungan_dideduplikasi_bot_dan_admin_dikecualikan(): void
    {
        $this->seedDasar();
        $this->get('/modul/pendahuluan', $this->ua)->assertOk();
        $this->get('/modul/pendahuluan', $this->ua)->assertOk();
        $this->get('/modul/pendahuluan', ['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])->assertOk();
        $versi = (string) app(HandleInertiaRequests::class)->version(request());
        $this->get('/modul/pendahuluan', $this->ua + ['X-Inertia' => 'true', 'X-Inertia-Version' => $versi, 'X-Inertia-Partial-Data' => 'bab', 'X-Inertia-Partial-Component' => 'publik/modul/show'])->assertOk();
        $this->get('/modul/tidak-ada', $this->ua)->assertNotFound();
        $this->actingAs($this->admin())->get('/modul/pendahuluan', $this->ua)->assertOk();

        $this->assertSame(1, KunjunganHalaman::query()->count());
        $k = KunjunganHalaman::query()->firstOrFail();
        $this->assertSame('/modul/pendahuluan', $k->jalur_halaman);
        $this->assertSame('ponsel', $k->jenis_perangkat);
        $this->assertSame(64, strlen($k->sidik_pengunjung));
        $this->assertStringNotContainsString('127.0.0.1', json_encode($k->toArray()));
    }

    public function test_dashboard_sesuai_agregasi_database_dan_demo_terpisah(): void
    {
        $this->seedDasar();
        $this->get('/', $this->ua);
        $this->get('/modul/sintaks-model', $this->ua);
        $this->seed(DataDemoSeeder::class);

        $admin = $this->admin();
        $nyata = $this->actingAs($admin)->get('/admin?hari=30')->assertOk()->viewData('page')['props'];
        $this->assertSame(2, $nyata['widget']['kunjungan']);
        $this->assertSame(1, $nyata['widget']['pengunjung_unik_perkiraan']);
        $this->assertSame(9, $nyata['widget']['bab_terbit']);
        $this->assertSame(10, $nyata['widget']['pdf_aktif']);
        $this->assertCount(30, $nyata['grafik']['kunjungan']);
        $this->assertTrue($nyata['filter']['ada_demo']);
        $this->assertSame(0, $nyata['widget']['pesan_baru']);

        $demo = $this->actingAs($admin)->get('/admin?hari=30&demo=1')->assertOk()->viewData('page')['props'];
        $dari = $demo['filter']['dari'];
        $harapan = (int) DB::table('kunjungan_halaman')->where('data_contoh', true)->whereBetween('tanggal_kunjungan', [$dari, $demo['filter']['sampai']])->count();
        $this->assertSame($harapan, $demo['widget']['kunjungan']);
        $this->assertSame($harapan, array_sum(array_column($demo['grafik']['kunjungan'], 'kunjungan')));
        $this->assertNotSame($nyata['widget']['kunjungan'], $demo['widget']['kunjungan']);

        $tabel = collect($nyata['tabel']['inventaris'])->keyBy('tabel');
        $this->assertSame(9, $tabel['bab_modul']['total']);
        $this->assertSame(16, $tabel['indikator_karakter']['total']);
        $this->assertArrayHasKey('token_atur_ulang_sandi', $tabel->all());
    }

    public function test_ekspor_csv_statistik_dan_pesan(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $r = $this->actingAs($admin)->get('/admin/statistik/ekspor')->assertOk();
        $this->assertStringContainsString('text/csv', $r->headers->get('Content-Type'));
        $this->assertStringContainsString('Tanggal;Kunjungan', $r->streamedContent());

        PesanKontak::query()->forceCreate(['nama_pengirim' => '=cmd|calc', 'surel_pengirim' => 'a@contoh.test', 'subjek' => '+SUM(1)', 'isi_pesan' => 'Isi pesan uji', 'persetujuan_privasi' => true, 'status' => 'baru', 'data_contoh' => false]);
        $csv = $this->actingAs($admin)->get('/admin/pesan/ekspor')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=cmd|calc", $csv);
        $this->assertStringContainsString("'+SUM(1)", $csv);
    }

    public function test_formulir_kontak_validasi_honeypot_persetujuan_dan_laju(): void
    {
        $this->seedDasar();
        RateLimiter::clear('kontak|127.0.0.1');
        $sah = ['nama_pengirim' => 'Bu Guru', 'surel_pengirim' => 'guru@contoh.test', 'subjek' => 'Pertanyaan fase', 'isi_pesan' => 'Apakah fase II dapat dilakukan enam kali sehari?', 'persetujuan_privasi' => true];

        $this->post('/kontak', ['nama_pengirim' => ''])->assertSessionHasErrors(['nama_pengirim', 'surel_pengirim', 'subjek', 'isi_pesan', 'persetujuan_privasi']);
        $this->post('/kontak', [...$sah, 'persetujuan_privasi' => false])->assertSessionHasErrors('persetujuan_privasi');
        $this->post('/kontak', [...$sah, 'situs_web' => 'http://spam.test'])->assertSessionHasErrors('situs_web');
        $this->assertSame(0, PesanKontak::query()->count());

        // Percobaan gagal ikut dihitung pembatas laju (3/menit); kosongkan sebelum kiriman sah.
        RateLimiter::clear(md5('kontak'.'kontak|127.0.0.1'));
        $this->post('/kontak', [...$sah, 'isi_pesan' => '<script>alert(1)</script>Halo apa kabar guru PAUD'])->assertRedirect('/kontak')->assertSessionHas('pesan_terkirim');
        $pesan = PesanKontak::query()->firstOrFail();
        $this->assertStringNotContainsString('<script>', $pesan->isi_pesan);
        $this->assertSame('baru', $pesan->status->value);

        // Isi pesan tidak tampil di halaman publik.
        $this->get('/kontak')->assertOk()->assertDontSee('Halo apa kabar guru PAUD', false);

        $kode = 0;
        for ($i = 0; $i < 5; $i++) {
            $kode = $this->post('/kontak', $sah)->status();
        }
        $this->assertSame(429, $kode);
    }

    public function test_status_pesan_dan_catatan_admin(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $p = PesanKontak::query()->forceCreate(['nama_pengirim' => 'X', 'surel_pengirim' => 'x@contoh.test', 'subjek' => 'Uji', 'isi_pesan' => 'Isi pesan rahasia', 'persetujuan_privasi' => true, 'status' => 'baru', 'data_contoh' => false]);
        $this->actingAs($admin)->get("/admin/pesan/{$p->id}")->assertOk();
        $this->assertSame('dibaca', $p->fresh()->status->value);
        $this->actingAs($admin)->patch("/admin/pesan/{$p->id}", ['status' => 'arsip', 'catatan_admin' => 'Sudah dijawab lewat surel'])->assertSessionHasNoErrors();
        $this->assertSame('arsip', $p->fresh()->status->value);
        $log = DB::table('log_aktivitas')->where('jenis_entitas', 'pesan_kontak')->get();
        $this->assertNotEmpty($log);
        $this->assertStringNotContainsString('Isi pesan rahasia', $log->pluck('perubahan')->implode(' '));
    }
}
