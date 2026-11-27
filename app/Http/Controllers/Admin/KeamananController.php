<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/** Pengubahan surel login & sandi memerlukan verifikasi sandi saat ini. */
class KeamananController extends AdminController
{
    public function edit(Request $request): Response
    {
        $u = $request->user();

        return Inertia::render('admin/keamanan/edit', [
            'akun' => ['surel' => $u->surel, 'sandi_diubah_pada' => $u->sandi_diubah_pada?->toIso8601String(), 'terakhir_masuk_pada' => $u->terakhir_masuk_pada?->toIso8601String(), 'wajib_ganti_sandi' => $u->wajib_ganti_sandi],
            'sesi_driver' => config('session.driver'),
        ]);
    }

    public function surel(Request $request): RedirectResponse
    {
        $u = $request->user();
        $data = $request->validate([
            'surel' => ['required', 'email', 'max:191', Rule::unique('pengguna', 'surel')->ignore($u->id)],
            'sandi_saat_ini' => ['required', 'current_password'],
        ], ['sandi_saat_ini.current_password' => 'Sandi saat ini tidak sesuai.'], ['surel' => 'surel login', 'sandi_saat_ini' => 'sandi saat ini']);

        $lama = $u->surel;
        $u->forceFill(['surel' => Str::lower($data['surel']), 'surel_diverifikasi_pada' => null])->save();
        $this->audit->catat('ganti_surel', 'pengguna', $u->id, 'Surel login diubah', ['surel' => ['sebelum' => Str::mask($lama, '*', 2, max(1, strpos($lama, '@') - 2)), 'sesudah' => Str::mask($u->surel, '*', 2, max(1, strpos($u->surel, '@') - 2))]]);

        return $this->sukses('Surel login diperbarui.');
    }

    public function sandi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sandi_saat_ini' => ['required', 'current_password'],
            'kata_sandi' => ['required', 'confirmed', Password::defaults(), 'different:sandi_saat_ini'],
            'keluarkan_sesi_lain' => ['boolean'],
        ], ['sandi_saat_ini.current_password' => 'Sandi saat ini tidak sesuai.', 'kata_sandi.different' => 'Sandi baru harus berbeda dari sandi saat ini.'], ['kata_sandi' => 'sandi baru', 'sandi_saat_ini' => 'sandi saat ini']);

        $u = $request->user();
        $u->forceFill(['kata_sandi' => Hash::make($data['kata_sandi']), 'sandi_diubah_pada' => now(), 'wajib_ganti_sandi' => false])->save();
        if ($request->boolean('keluarkan_sesi_lain', true)) {
            // Memperbarui hash sandi membatalkan sesi lain (AuthenticateSession) dan token "ingat saya".
            Auth::logoutOtherDevices($data['kata_sandi']);
            $u->forceFill(['token_pengingat' => Str::random(60)])->save();
        }
        $request->session()->regenerate();
        $this->audit->catat('ganti_sandi', 'pengguna', $u->id, 'Sandi admin diubah'.($request->boolean('keluarkan_sesi_lain', true) ? ' dan sesi lain dicabut' : ''));

        return $this->sukses('Sandi diperbarui.');
    }

    public function keluarPerangkatLain(Request $request): RedirectResponse
    {
        $request->validate(['sandi_saat_ini' => ['required', 'current_password']], ['sandi_saat_ini.current_password' => 'Sandi saat ini tidak sesuai.']);
        Auth::logoutOtherDevices($request->input('sandi_saat_ini'));
        $request->session()->regenerate();
        $this->audit->catat('cabut_sesi', 'pengguna', $request->user()->id, 'Keluar dari perangkat lain');

        return $this->sukses('Semua sesi di perangkat lain telah dikeluarkan.');
    }
}
