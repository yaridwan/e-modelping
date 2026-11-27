<?php

use App\Models\Pengguna;

return [

    /*
    | Autentikasi hanya untuk admin (pemilik website). Tidak ada registrasi publik.
    | Model `Pengguna` memetakan kontrak autentikasi ke kolom berbahasa Indonesia
    | (`surel`, `kata_sandi`, `token_pengingat`).
    */

    'defaults' => [
        'guard' => 'web',
        // Pemulihan sandi tidak memakai broker bawaan (yang mengharapkan kolom Inggris),
        // melainkan App\Layanan\LayananAturUlangSandi dengan tabel `token_atur_ulang_sandi`.
        'passwords' => 'pengguna',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'pengguna',
        ],
    ],

    'providers' => [
        'pengguna' => [
            'driver' => 'eloquent',
            'model' => Pengguna::class,
        ],
    ],

    'passwords' => [
        'pengguna' => [
            'provider' => 'pengguna',
            'table' => 'token_atur_ulang_sandi',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    // Detik sebelum konfirmasi sandi diminta ulang untuk aksi sensitif.
    'password_timeout' => 10800,

];
