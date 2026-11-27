<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Layanan\LayananAturUlangSandi;
use App\Layanan\LayananSeo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AturUlangSandiController extends Controller
{
    public function create(string $token, LayananAturUlangSandi $layanan, LayananSeo $seo): Response
    {
        return Inertia::render('auth/atur-ulang-sandi', [
            'token' => $token,
            'valid' => (bool) $layanan->tokenValid($token),
            'seo' => $seo->bangun(['judul' => 'Atur Ulang Sandi', 'jalur' => '/admin/lupa-sandi', 'robots' => 'noindex,nofollow']),
        ]);
    }

    public function store(Request $request, LayananAturUlangSandi $layanan): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'surel' => ['required', 'email', 'max:191'],
            'kata_sandi' => ['required', 'confirmed', Password::defaults()],
        ], [], ['surel' => 'surel', 'kata_sandi' => 'kata sandi baru']);

        if (! $layanan->aturUlang($data['token'], $data['surel'], $data['kata_sandi'])) {
            throw ValidationException::withMessages(['surel' => 'Tautan tidak valid, sudah dipakai, atau kedaluwarsa, atau surel tidak sesuai. Minta tautan baru.']);
        }

        return redirect('/admin/masuk')->with('status', 'Sandi berhasil diatur ulang. Silakan masuk dengan sandi baru.');
    }
}
