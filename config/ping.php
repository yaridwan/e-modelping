<?php

/*
| Konfigurasi khusus aplikasi E-Model PING. Nilai rahasia tetap berada di environment.
*/

return [

    // Batas keras ukuran unggahan (MB). Admin dapat menurunkan batas di Pengaturan > Dokumen,
    // tetapi tidak dapat melampaui nilai ini. Selaraskan dengan upload_max_filesize,
    // post_max_size (PHP) dan client_max_body_size (Nginx).
    'batas_unggah_maksimum_mb' => (int) env('PING_BATAS_UNGGAH_MAKS_MB', 25),

    // Batas sumber daya saat mem-parsing PDF unggahan.
    'pdf' => [
        'batas_memori_parser' => env('PING_PDF_MEMORI', '256M'),
        'maks_halaman' => (int) env('PING_PDF_MAKS_HALAMAN', 500),
    ],

    'gambar' => [
        'maks_piksel_sisi' => 4000,
        'mime_diizinkan' => ['image/jpeg', 'image/png', 'image/webp'],
    ],

    // Penyedia video eksternal yang diizinkan (tidak ada fetch server ke URL ini).
    'video_diizinkan' => ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'vimeo.com', 'player.vimeo.com'],

    'admin_awal' => [
        'surel' => env('ADMIN_AWAL_SUREL', 'admin@emodelping.test'),
        'nama' => env('ADMIN_AWAL_NAMA', 'Admin E-Model PING'),
        'sandi' => env('ADMIN_AWAL_SANDI'),
    ],

    'captcha' => [
        'masa_berlaku_detik' => 300,
    ],

    'login' => [
        'maks_percobaan_per_identitas' => 5,
        'maks_percobaan_per_ip' => 20,
        'jeda_detik' => 60,
    ],

    // Statistik: kunjungan halaman dihitung satu kali per pengunjung (sidik pseudonim),
    // per jalur, per tanggal lokal. Aktivitas dokumen dihitung satu kali per pengunjung,
    // per versi, per jenis (baca/unduh), per tanggal lokal.
    'statistik' => [
        'pola_bot' => '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|headless|lighthouse|pagespeed|curl|wget|python-requests|httpclient|monitor|uptime|preview/i',
    ],

];
