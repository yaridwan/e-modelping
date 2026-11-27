<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Layanan\LayananAturUlangSandi;
use App\Layanan\LayananSeo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LupaSandiController extends Controller
{
    public function create(Request $request, LayananSeo $seo): Response
    {
        return Inertia::render('auth/lupa-sandi', [
            'status' => $request->session()->get('status'),
            'seo' => $seo->bangun(['judul' => 'Lupa Sandi', 'jalur' => '/admin/lupa-sandi', 'robots' => 'noindex,nofollow']),
        ]);
    }

    public function store(Request $request, LayananAturUlangSandi $layanan): RedirectResponse
    {
        $data = $request->validate(['surel' => ['required', 'email', 'max:191']], [], ['surel' => 'surel']);
        $layanan->kirimTautan($data['surel']);

        // Respons generik: sama untuk surel terdaftar maupun tidak.
        return back()->with('status', 'Jika surel tersebut terdaftar, tautan atur ulang sandi telah dikirim. Tautan berlaku 60 menit.');
    }
}
