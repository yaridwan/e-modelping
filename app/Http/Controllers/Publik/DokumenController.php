<?php

namespace App\Http\Controllers\Publik;

use App\Http\Resources\VersiDokumenPublikResource;
use App\Layanan\LayananDokumen;
use App\Layanan\LayananSeo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Dokumen publik. Setiap endpoint memeriksa: versi terbit, versi aktif, induk bab terbit
 * (untuk dokumen bab), dan untuk unduhan izin unduh global + per dokumen.
 * Menonaktifkan unduhan adalah kebijakan antarmuka & endpoint, bukan DRM.
 */
class DokumenController extends PublikController
{
    /** Halaman HTML pembaca dokumen (penampil PDF.js). */
    public function baca(Request $request, int $versi, LayananDokumen $dokumen): Response
    {
        $v = $dokumen->versiPublik($versi);
        $this->tandai($request, 'dokumen', $v->bab_id);
        $judul = $v->judul_dokumen;
        $kembali = $v->bab ? ['label' => 'Bab '.$v->bab->nomor_romawi.' '.$v->bab->judul, 'url' => '/modul/'.$v->bab->slug] : ['label' => 'Daftar bab', 'url' => '/modul'];

        return Inertia::render('publik/dokumen', [
            'dokumen' => (new VersiDokumenPublikResource($v))->resolve($request),
            'kembali' => $kembali,
            'seo' => $this->seo->bangun([
                'judul' => $judul,
                'deskripsi' => 'Baca dokumen PDF '.$judul.' (versi '.$v->nomor_versi.').',
                'jalur' => '/dokumen/'.$v->id.'/baca',
                // Halaman HTML bab adalah pintu utama pencarian; penampil PDF tidak diindeks.
                'robots' => 'noindex,follow',
                'jsonLd' => [LayananSeo::jsonLdBreadcrumb([['nama' => 'Beranda', 'url' => '/'], ['nama' => 'Modul', 'url' => '/modul'], ['nama' => $judul, 'url' => '/dokumen/'.$v->id.'/baca']])],
            ]),
        ]);
    }

    /** Berkas PDF inline (dipakai penampil dan tombol "buka di tab baru"); mendukung Range/206. */
    public function berkas(Request $request, int $versi, LayananDokumen $dokumen): BinaryFileResponse
    {
        return $dokumen->sajikan($dokumen->versiPublik($versi), $request, 'baca');
    }

    public function unduh(Request $request, int $versi, LayananDokumen $dokumen): BinaryFileResponse
    {
        $v = $dokumen->versiPublik($versi);
        abort_unless($dokumen->izinUnduhEfektif($v), 403, 'Unduhan dokumen ini tidak diizinkan.');

        return $dokumen->sajikan($v, $request, 'unduh');
    }
}
