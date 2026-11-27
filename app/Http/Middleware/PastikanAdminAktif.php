<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Akun yang dinonaktifkan langsung dikeluarkan dari sesi. */
class PastikanAdminAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();
        if ($pengguna && ! $pengguna->aktif()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/admin/masuk')->with('galat', 'Akun tidak aktif.');
        }

        return $next($request);
    }
}
