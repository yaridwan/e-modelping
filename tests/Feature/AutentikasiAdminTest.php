<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\TokenAturUlangSandi;
use App\Notifications\TautanAturUlangSandi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AutentikasiAdminTest extends TestCase
{
    use RefreshDatabase;

    private function masuk(array $data = [], ?array $captcha = null): TestResponse
    {
        $captcha ??= $this->captchaBaru();

        return $this->post('/admin/masuk', array_merge([
            'surel' => 'admin@emodelping.test',
            'kata_sandi' => 'SandiUji12345',
            'captcha_id' => $captcha['id'],
            'captcha_jawaban' => $captcha['jawaban'],
        ], $data));
    }

    public function test_beranda_publik_bukan_redirect_login_dan_tidak_ada_registrasi(): void
    {
        $this->seedDasar();
        $this->get('/')->assertOk()->assertSee('Bangun Kebiasaan Baik Anak melalui Model PING', false);
        $this->get('/register')->assertNotFound();
        $this->get('/admin/daftar')->assertNotFound();
        $this->assertContains($this->post('/register', ['email' => 'x@y.z'])->status(), [404, 405]);
    }

    public function test_halaman_masuk_tidak_membocorkan_jawaban_captcha(): void
    {
        $r = $this->get('/admin/masuk')->assertOk();
        $props = $r->viewData('page')['props'];
        $this->assertArrayHasKey('captcha', $props);
        $this->assertSame(['id', 'soal', 'kedaluwarsa_detik'], array_keys($props['captcha']));
        $this->assertStringNotContainsString('jawaban', strtolower(json_encode($props['captcha'])));
        $this->assertStringNotContainsString('"hash"', $r->getContent());
        $r->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_masuk_berhasil_meregenerasi_sesi_dan_memakai_kolom_indonesia(): void
    {
        $admin = $this->admin();
        $this->startSession();
        $idLama = session()->getId();
        $this->masuk(['ingat_saya' => true])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->assertNotSame($idLama, session()->getId());
        $this->assertNotNull($admin->fresh()->token_pengingat);
        $this->assertNotNull($admin->fresh()->terakhir_masuk_pada);
    }

    public function test_captcha_salah_kedaluwarsa_dan_replay_ditolak(): void
    {
        $this->admin();
        $c = $this->captchaBaru();
        $this->masuk(['captcha_jawaban' => (string) ((int) $c['jawaban'] + 1)], $c)->assertSessionHasErrors('captcha_jawaban');
        $this->assertGuest();

        // Replay: ID yang sama tidak dapat dipakai ulang meski jawabannya benar.
        $this->masuk([], $c)->assertSessionHasErrors('captcha_jawaban');
        $this->assertGuest();

        // Kedaluwarsa.
        $c2 = $this->captchaBaru();
        $this->travel(6)->minutes();
        $this->masuk([], $c2)->assertSessionHasErrors(['captcha_jawaban' => 'Soal keamanan sudah kedaluwarsa. Silakan jawab soal yang baru.']);
        $this->assertGuest();
    }

    public function test_kredensial_salah_pesan_generik_dan_akun_nonaktif_ditolak(): void
    {
        $this->admin();
        $this->masuk(['kata_sandi' => 'salah'])->assertSessionHasErrors(['surel' => 'Surel atau kata sandi tidak sesuai.']);
        $this->masuk(['surel' => 'tidakada@contoh.test'])->assertSessionHasErrors(['surel' => 'Surel atau kata sandi tidak sesuai.']);
        Pengguna::query()->update(['status' => 'nonaktif']);
        $this->masuk()->assertSessionHasErrors(['surel' => 'Surel atau kata sandi tidak sesuai.']);
        $this->assertGuest();
    }

    public function test_pembatas_laju_percobaan_masuk(): void
    {
        $this->admin();
        RateLimiter::clear('masuk-ip|127.0.0.1');
        for ($i = 0; $i < 5; $i++) {
            $this->masuk(['kata_sandi' => 'salah'.$i]);
        }
        $this->masuk()->assertSessionHasErrors('surel');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('surel'));
        $this->assertGuest();
    }

    public function test_keluar_menginvalidasi_sesi(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/keluar')->assertRedirect('/admin/masuk');
        $this->assertGuest();
        $this->get('/admin')->assertRedirect('/admin/masuk');
    }

    public function test_atur_ulang_sandi_token_hash_sekali_pakai(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $this->post('/admin/lupa-sandi', ['surel' => 'tidakada@contoh.test'])->assertSessionHas('status');
        Notification::assertNothingSent();

        $this->post('/admin/lupa-sandi', ['surel' => $admin->surel])->assertSessionHas('status');
        $url = null;
        Notification::assertSentTo($admin, TautanAturUlangSandi::class, function ($n) use (&$url, $admin) {
            $url = $n->toMail($admin)->actionUrl;

            return true;
        });
        $this->assertStringStartsWith('http://ping.test/admin/atur-ulang-sandi/', $url);
        $token = basename($url);
        $catatan = TokenAturUlangSandi::query()->firstOrFail();
        $this->assertNotSame($token, $catatan->token_hash);
        $this->assertSame(hash_hmac('sha256', $token, config('app.key')), $catatan->token_hash);

        $this->get('/admin/atur-ulang-sandi/'.$token)->assertOk();
        $this->post('/admin/atur-ulang-sandi', ['token' => $token, 'surel' => $admin->surel, 'kata_sandi' => 'SandiBaru2026', 'kata_sandi_confirmation' => 'SandiBaru2026'])->assertRedirect('/admin/masuk');
        $this->assertTrue(Hash::check('SandiBaru2026', $admin->fresh()->kata_sandi));

        // Sekali pakai.
        $this->post('/admin/atur-ulang-sandi', ['token' => $token, 'surel' => $admin->surel, 'kata_sandi' => 'SandiLain2026', 'kata_sandi_confirmation' => 'SandiLain2026'])->assertSessionHasErrors('surel');
        $this->assertTrue(Hash::check('SandiBaru2026', $admin->fresh()->kata_sandi));
    }
}
