<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MasukRequest;
use App\Layanan\LayananAudit;
use App\Layanan\LayananCaptcha;
use App\Layanan\LayananSeo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MasukController extends Controller
{
    public function create(Request $request, LayananCaptcha $captcha, LayananSeo $seo): Response
    {
        return Inertia::render('auth/masuk', [
            // Setiap render (termasuk partial reload "Ganti soal") menghasilkan tantangan baru.
            // Hanya ID dan teks soal yang dikirim; jawaban tetap di server.
            'captcha' => fn () => collect($captcha->buat())->only(['id', 'soal', 'kedaluwarsa_detik'])->all(),
            'status' => $request->session()->get('status'),
            'seo' => $seo->bangun(['judul' => 'Masuk Admin', 'jalur' => '/admin/masuk', 'robots' => 'noindex,nofollow']),
        ]);
    }

    public function store(MasukRequest $request, LayananCaptcha $captcha, LayananAudit $audit): RedirectResponse
    {
        $pengguna = $request->autentikasi($captcha, $audit);

        $request->session()->regenerate();
        $pengguna->forceFill(['terakhir_masuk_pada' => now()])->saveQuietly();
        $audit->catat('masuk', 'pengguna', $pengguna->id, 'Admin masuk', null, $pengguna->id);

        return redirect()->intended('/admin');
    }

    public function destroy(Request $request, LayananAudit $audit): RedirectResponse
    {
        $id = $request->user()?->id;
        if ($id) {
            $audit->catat('keluar', 'pengguna', $id, 'Admin keluar', null, $id);
        }
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/masuk')->with('status', 'Anda telah keluar.');
    }
}
