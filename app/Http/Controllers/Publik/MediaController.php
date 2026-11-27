<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Layanan\LayananMedia;
use App\Models\BerkasMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Gambar publik. Hak akses ditentukan konteks publikasi (dirujuk konten terbit/identitas situs),
 * bukan sekadar mengetahui UUID. Admin terautentikasi dapat melihat semua gambar.
 */
class MediaController extends Controller
{
    public function tampil(Request $request, string $uuid, string $ekstensi, LayananMedia $layanan): BinaryFileResponse
    {
        $media = BerkasMedia::query()->where('uuid', $uuid)->where('jenis_media', 'gambar')->firstOrFail();
        abort_unless($media->ekstensi === $ekstensi, 404);
        $admin = (bool) $request->user();
        abort_unless($admin || $layanan->bolehPublik($media), 404);

        $disk = Storage::disk($media->lokasi_penyimpanan ?: LayananMedia::DISK);
        abort_unless($media->jalur_berkas && $disk->exists($media->jalur_berkas), 404);

        $response = new BinaryFileResponse($disk->path($media->jalur_berkas), 200, [
            'Content-Type' => $media->tipe_mime,
            'X-Content-Type-Options' => 'nosniff',
        ], true, 'inline', true, true);
        if ($admin && ! $layanan->bolehPublik($media)) {
            $response->headers->set('Cache-Control', 'private, no-store');
        } else {
            $response->setPublic();
            $response->setMaxAge(86400);
        }

        return $response;
    }
}
