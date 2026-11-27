<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Publik;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute publik (tanpa login)
|--------------------------------------------------------------------------
*/

Route::get('/', [Publik\BerandaController::class, 'index'])->name('beranda');
Route::get('/tentang', [Publik\TentangController::class, 'index'])->name('tentang');
Route::get('/modul', [Publik\ModulController::class, 'index'])->name('modul.index');
Route::get('/modul/{slug}', [Publik\ModulController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('modul.show');
Route::get('/fase/{slug}', [Publik\FaseController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('fase.show');
Route::get('/karakter', [Publik\KarakterController::class, 'index'])->name('karakter');
Route::get('/panduan-penggunaan', [Publik\HalamanController::class, 'panduan'])->name('panduan');
Route::get('/galeri', [Publik\GaleriController::class, 'index'])->name('galeri');
Route::get('/pertanyaan-umum', [Publik\PertanyaanUmumController::class, 'index'])->name('faq');
Route::get('/kontak', [Publik\KontakController::class, 'create'])->name('kontak');
Route::post('/kontak', [Publik\KontakController::class, 'store'])->middleware('throttle:kontak')->name('kontak.store');
Route::get('/kebijakan-privasi', [Publik\HalamanController::class, 'privasi'])->name('privasi');
Route::get('/cari', [Publik\CariController::class, 'index'])->name('cari');

Route::middleware('throttle:dokumen')->group(function () {
    Route::get('/dokumen/{versi}/baca', [Publik\DokumenController::class, 'baca'])->whereNumber('versi')->name('dokumen.baca');
    Route::get('/dokumen/{versi}/unduh', [Publik\DokumenController::class, 'unduh'])->whereNumber('versi')->name('dokumen.unduh');
    Route::get('/dokumen/{versi}/berkas', [Publik\DokumenController::class, 'berkas'])->whereNumber('versi')->name('dokumen.berkas');
});

Route::get('/media/{uuid}.{ekstensi}', [Publik\MediaController::class, 'tampil'])
    ->where(['uuid' => '[0-9a-f\-]{36}', 'ekstensi' => 'jpg|png|webp'])->name('media.tampil');

Route::get('/sitemap.xml', [Publik\SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [Publik\SeoController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Autentikasi admin (tidak ada registrasi publik)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/masuk', [Auth\MasukController::class, 'create'])->name('masuk');
        Route::post('/masuk', [Auth\MasukController::class, 'store'])->name('masuk.store');
        Route::get('/lupa-sandi', [Auth\LupaSandiController::class, 'create'])->name('sandi.lupa');
        Route::post('/lupa-sandi', [Auth\LupaSandiController::class, 'store'])->middleware('throttle:lupa-sandi')->name('sandi.kirim');
        Route::get('/atur-ulang-sandi/{token}', [Auth\AturUlangSandiController::class, 'create'])->where('token', '[A-Za-z0-9]{64}')->name('sandi.atur');
        Route::post('/atur-ulang-sandi', [Auth\AturUlangSandiController::class, 'store'])->middleware('throttle:atur-ulang-sandi')->name('sandi.simpan');
    });

    Route::post('/keluar', [Auth\MasukController::class, 'destroy'])->middleware('auth')->name('keluar');
});

/*
|--------------------------------------------------------------------------
| Panel admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'auth.session', 'admin.aktif', 'can:kelola-website'])->group(function () {
    Route::get('/', [Admin\DasborController::class, 'index'])->name('dasbor');

    // Bab & subbab (struktur sembilan bab dijaga: tidak ada rute buat/hapus bab).
    Route::get('/bab', [Admin\BabController::class, 'index'])->name('bab.index');
    Route::get('/bab/{bab}/edit', [Admin\BabController::class, 'edit'])->name('bab.edit');
    Route::put('/bab/{bab}', [Admin\BabController::class, 'update'])->name('bab.update');
    Route::post('/bab/{bab}/status', [Admin\BabController::class, 'status'])->name('bab.status');
    Route::get('/bab/{bab}/pratinjau', [Admin\BabController::class, 'pratinjau'])->name('bab.pratinjau');
    Route::get('/bab/{bab}/subbab/buat', [Admin\SubbabController::class, 'create'])->name('subbab.create');
    Route::post('/bab/{bab}/subbab', [Admin\SubbabController::class, 'store'])->name('subbab.store');
    Route::post('/bab/{bab}/subbab/urutan', [Admin\SubbabController::class, 'urutan'])->name('subbab.urutan');
    Route::get('/bab/{bab}/subbab/{subbab}/edit', [Admin\SubbabController::class, 'edit'])->name('subbab.edit');
    Route::put('/bab/{bab}/subbab/{subbab}', [Admin\SubbabController::class, 'update'])->name('subbab.update');
    Route::post('/bab/{bab}/subbab/{subbab}/status', [Admin\SubbabController::class, 'status'])->name('subbab.status');
    Route::delete('/bab/{bab}/subbab/{subbab}', [Admin\SubbabController::class, 'destroy'])->name('subbab.destroy');

    // Dokumen PDF & versi.
    Route::get('/dokumen', [Admin\DokumenController::class, 'index'])->name('dokumen.index');
    Route::get('/dokumen/unggah', [Admin\DokumenController::class, 'create'])->name('dokumen.create');
    Route::post('/dokumen', [Admin\DokumenController::class, 'store'])->name('dokumen.store');
    Route::get('/dokumen/{versi}', [Admin\DokumenController::class, 'show'])->name('dokumen.show');
    Route::patch('/dokumen/{versi}', [Admin\DokumenController::class, 'update'])->name('dokumen.update');
    Route::post('/dokumen/{versi}/aktifkan', [Admin\DokumenController::class, 'aktifkan'])->name('dokumen.aktifkan');
    Route::post('/dokumen/{versi}/arsipkan', [Admin\DokumenController::class, 'arsipkan'])->name('dokumen.arsipkan');
    Route::delete('/dokumen/{versi}', [Admin\DokumenController::class, 'destroy'])->name('dokumen.destroy');
    Route::get('/dokumen/{versi}/pratinjau', [Admin\DokumenController::class, 'pratinjau'])->name('dokumen.pratinjau');

    // Fase PING & langkah.
    Route::get('/fase', [Admin\FaseController::class, 'index'])->name('fase.index');
    Route::get('/fase/{fase}/edit', [Admin\FaseController::class, 'edit'])->name('fase.edit');
    Route::put('/fase/{fase}', [Admin\FaseController::class, 'update'])->name('fase.update');
    Route::post('/fase/{fase}/langkah', [Admin\LangkahFaseController::class, 'store'])->name('langkah.store');
    Route::post('/fase/{fase}/langkah/urutan', [Admin\LangkahFaseController::class, 'urutan'])->name('langkah.urutan');
    Route::put('/fase/{fase}/langkah/{langkah}', [Admin\LangkahFaseController::class, 'update'])->name('langkah.update');
    Route::delete('/fase/{fase}/langkah/{langkah}', [Admin\LangkahFaseController::class, 'destroy'])->name('langkah.destroy');

    // Aspek & indikator karakter (referensi materi).
    Route::get('/karakter', [Admin\KarakterController::class, 'index'])->name('karakter.index');
    Route::put('/karakter/aspek/{aspek}', [Admin\KarakterController::class, 'updateAspek'])->name('karakter.aspek');
    Route::put('/karakter/indikator/{indikator}', [Admin\KarakterController::class, 'updateIndikator'])->name('karakter.indikator');

    // Beranda dinamis.
    Route::get('/beranda', [Admin\BerandaController::class, 'index'])->name('beranda.index');
    Route::put('/beranda/{bagian}', [Admin\BerandaController::class, 'update'])->name('beranda.update');
    Route::post('/beranda/urutan', [Admin\BerandaController::class, 'urutan'])->name('beranda.urutan');

    // Halaman informasi.
    Route::get('/halaman', [Admin\HalamanController::class, 'index'])->name('halaman.index');
    Route::get('/halaman/buat', [Admin\HalamanController::class, 'create'])->name('halaman.create');
    Route::post('/halaman', [Admin\HalamanController::class, 'store'])->name('halaman.store');
    Route::get('/halaman/{halaman}/edit', [Admin\HalamanController::class, 'edit'])->name('halaman.edit');
    Route::put('/halaman/{halaman}', [Admin\HalamanController::class, 'update'])->name('halaman.update');
    Route::post('/halaman/{halaman}/status', [Admin\HalamanController::class, 'status'])->name('halaman.status');
    Route::delete('/halaman/{halaman}', [Admin\HalamanController::class, 'destroy'])->name('halaman.destroy');

    // Pustaka media.
    Route::get('/media', [Admin\MediaController::class, 'index'])->name('media.index');
    Route::get('/media/pilih', [Admin\MediaController::class, 'pilih'])->name('media.pilih');
    Route::post('/media', [Admin\MediaController::class, 'store'])->name('media.store');
    Route::get('/media/{media}', [Admin\MediaController::class, 'show'])->name('media.show');
    Route::put('/media/{media}', [Admin\MediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{media}', [Admin\MediaController::class, 'destroy'])->name('media.destroy');
    Route::get('/media/{media}/berkas', [Admin\MediaController::class, 'berkas'])->name('media.berkas');

    // Galeri, FAQ, referensi.
    Route::get('/galeri', [Admin\GaleriController::class, 'index'])->name('galeri.index');
    Route::get('/galeri/buat', [Admin\GaleriController::class, 'create'])->name('galeri.create');
    Route::post('/galeri', [Admin\GaleriController::class, 'store'])->name('galeri.store');
    Route::get('/galeri/{galeri}/edit', [Admin\GaleriController::class, 'edit'])->name('galeri.edit');
    Route::put('/galeri/{galeri}', [Admin\GaleriController::class, 'update'])->name('galeri.update');
    Route::post('/galeri/{galeri}/status', [Admin\GaleriController::class, 'status'])->name('galeri.status');
    Route::delete('/galeri/{galeri}', [Admin\GaleriController::class, 'destroy'])->name('galeri.destroy');

    Route::get('/faq', [Admin\PertanyaanUmumController::class, 'index'])->name('faq.index');
    Route::post('/faq', [Admin\PertanyaanUmumController::class, 'store'])->name('faq.store');
    Route::post('/faq/urutan', [Admin\PertanyaanUmumController::class, 'urutan'])->name('faq.urutan');
    Route::put('/faq/{faq}', [Admin\PertanyaanUmumController::class, 'update'])->name('faq.update');
    Route::delete('/faq/{faq}', [Admin\PertanyaanUmumController::class, 'destroy'])->name('faq.destroy');

    Route::get('/referensi', [Admin\ReferensiController::class, 'index'])->name('referensi.index');
    Route::post('/referensi', [Admin\ReferensiController::class, 'store'])->name('referensi.store');
    Route::put('/referensi/{referensi}', [Admin\ReferensiController::class, 'update'])->name('referensi.update');
    Route::delete('/referensi/{referensi}', [Admin\ReferensiController::class, 'destroy'])->name('referensi.destroy');

    // Navigasi & tautan sosial.
    Route::get('/menu', [Admin\MenuController::class, 'index'])->name('menu.index');
    Route::post('/menu', [Admin\MenuController::class, 'store'])->name('menu.store');
    Route::post('/menu/urutan', [Admin\MenuController::class, 'urutan'])->name('menu.urutan');
    Route::put('/menu/{menu}', [Admin\MenuController::class, 'update'])->name('menu.update');
    Route::delete('/menu/{menu}', [Admin\MenuController::class, 'destroy'])->name('menu.destroy');
    Route::post('/tautan-sosial', [Admin\TautanSosialController::class, 'store'])->name('sosial.store');
    Route::put('/tautan-sosial/{tautan}', [Admin\TautanSosialController::class, 'update'])->name('sosial.update');
    Route::delete('/tautan-sosial/{tautan}', [Admin\TautanSosialController::class, 'destroy'])->name('sosial.destroy');

    // Pengalihan URL.
    Route::get('/pengalihan', [Admin\PengalihanController::class, 'index'])->name('pengalihan.index');
    Route::post('/pengalihan', [Admin\PengalihanController::class, 'store'])->name('pengalihan.store');
    Route::put('/pengalihan/{pengalihan}', [Admin\PengalihanController::class, 'update'])->name('pengalihan.update');
    Route::delete('/pengalihan/{pengalihan}', [Admin\PengalihanController::class, 'destroy'])->name('pengalihan.destroy');

    // Pesan masuk.
    Route::get('/pesan', [Admin\PesanController::class, 'index'])->name('pesan.index');
    Route::get('/pesan/ekspor', [Admin\PesanController::class, 'ekspor'])->name('pesan.ekspor');
    Route::post('/pesan/massal', [Admin\PesanController::class, 'massal'])->name('pesan.massal');
    Route::get('/pesan/{pesan}', [Admin\PesanController::class, 'show'])->whereNumber('pesan')->name('pesan.show');
    Route::patch('/pesan/{pesan}', [Admin\PesanController::class, 'update'])->whereNumber('pesan')->name('pesan.update');
    Route::patch('/pesan/{pesan}/status', [Admin\PesanController::class, 'status'])->whereNumber('pesan')->name('pesan.status');
    Route::delete('/pesan/{pesan}', [Admin\PesanController::class, 'destroy'])->whereNumber('pesan')->name('pesan.destroy');

    // Statistik & audit.
    Route::get('/statistik', [Admin\StatistikController::class, 'index'])->name('statistik.index');
    Route::get('/statistik/ekspor', [Admin\StatistikController::class, 'ekspor'])->name('statistik.ekspor');
    Route::get('/log-aktivitas', [Admin\LogAktivitasController::class, 'index'])->name('log.index');

    // Pengaturan, profil, keamanan.
    Route::get('/pengaturan', [Admin\PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::post('/pengaturan/{tab}', [Admin\PengaturanController::class, 'update'])->where('tab', 'identitas|kontak|tampilan|seo|dokumen|privasi')->name('pengaturan.update');

    Route::get('/profil', [Admin\ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [Admin\ProfilController::class, 'update'])->name('profil.update');
    Route::post('/profil/foto', [Admin\ProfilController::class, 'unggahFoto'])->name('profil.foto');
    Route::delete('/profil/foto', [Admin\ProfilController::class, 'hapusFoto'])->name('profil.foto.hapus');
    Route::put('/profil/tema', [Admin\ProfilController::class, 'tema'])->name('profil.tema');

    Route::get('/keamanan', [Admin\KeamananController::class, 'edit'])->name('keamanan.edit');
    Route::put('/keamanan/surel', [Admin\KeamananController::class, 'surel'])->name('keamanan.surel');
    Route::put('/keamanan/sandi', [Admin\KeamananController::class, 'sandi'])->name('keamanan.sandi');
    Route::post('/keamanan/keluar-perangkat-lain', [Admin\KeamananController::class, 'keluarPerangkatLain'])->name('keamanan.sesi');
});

// Halaman informasi tambahan dengan slug bebas (setelah semua rute eksplisit).
Route::get('/{slug}', [Publik\HalamanController::class, 'show'])
    ->where('slug', '(?!admin$|api$|build$|storage$|up$)[a-z0-9\-]+')
    ->name('halaman.show');
