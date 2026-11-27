<?php

namespace App\Http\Requests\Auth;

use App\Layanan\LayananAudit;
use App\Layanan\LayananCaptcha;
use App\Models\Pengguna;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MasukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'surel' => ['required', 'string', 'email', 'max:191'],
            'kata_sandi' => ['required', 'string', 'max:255'],
            'captcha_id' => ['required', 'string', 'size:32'],
            'captcha_jawaban' => ['required', 'string', 'max:3'],
            'ingat_saya' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['surel' => 'surel', 'kata_sandi' => 'kata sandi', 'captcha_jawaban' => 'jawaban soal keamanan'];
    }

    /**
     * Urutan: batas percobaan -> captcha (sekali pakai) -> kredensial -> status akun.
     * Pesan kegagalan kredensial generik agar tidak membocorkan keberadaan akun.
     */
    public function autentikasi(LayananCaptcha $captcha, LayananAudit $audit): Pengguna
    {
        $this->pastikanTidakDibatasi();

        $hasil = $captcha->verifikasi($this->input('captcha_id'), $this->input('captcha_jawaban'));
        if ($hasil !== 'valid') {
            $this->catatGagal();
            throw ValidationException::withMessages(['captcha_jawaban' => LayananCaptcha::pesan($hasil)]);
        }

        $kredensial = ['surel' => $this->surelNormal(), 'password' => (string) $this->input('kata_sandi')];
        if (! Auth::validate($kredensial)) {
            $this->catatGagal();
            $audit->catat('masuk_gagal', 'pengguna', null, 'Percobaan masuk gagal');
            throw ValidationException::withMessages(['surel' => 'Surel atau kata sandi tidak sesuai.']);
        }

        /** @var Pengguna $pengguna */
        $pengguna = Pengguna::query()->where('surel', $this->surelNormal())->firstOrFail();
        if (! $pengguna->aktif()) {
            $this->catatGagal();
            throw ValidationException::withMessages(['surel' => 'Surel atau kata sandi tidak sesuai.']);
        }

        Auth::login($pengguna, $this->boolean('ingat_saya'));
        RateLimiter::clear($this->kunciIdentitas());

        return $pengguna;
    }

    public function surelNormal(): string
    {
        return Str::lower(trim((string) $this->input('surel')));
    }

    private function pastikanTidakDibatasi(): void
    {
        $maksIdentitas = (int) config('ping.login.maks_percobaan_per_identitas', 5);
        $maksIp = (int) config('ping.login.maks_percobaan_per_ip', 20);
        if (! RateLimiter::tooManyAttempts($this->kunciIdentitas(), $maksIdentitas) && ! RateLimiter::tooManyAttempts($this->kunciIp(), $maksIp)) {
            return;
        }

        event(new Lockout($this));
        $detik = max(RateLimiter::availableIn($this->kunciIdentitas()), RateLimiter::availableIn($this->kunciIp()));
        throw ValidationException::withMessages([
            'surel' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$detik} detik.",
        ]);
    }

    private function catatGagal(): void
    {
        $jeda = (int) config('ping.login.jeda_detik', 60);
        RateLimiter::hit($this->kunciIdentitas(), $jeda);
        RateLimiter::hit($this->kunciIp(), $jeda);
    }

    private function kunciIdentitas(): string
    {
        return 'masuk|'.hash('sha256', $this->surelNormal()).'|'.$this->ip();
    }

    private function kunciIp(): string
    {
        return 'masuk-ip|'.$this->ip();
    }
}
