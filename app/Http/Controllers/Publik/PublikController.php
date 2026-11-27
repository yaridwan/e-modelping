<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Layanan\LayananPengaturan;
use App\Layanan\LayananSeo;
use App\Models\Pengguna;
use Illuminate\Http\Request;

abstract class PublikController extends Controller
{
    public function __construct(
        protected readonly LayananSeo $seo,
        protected readonly LayananPengaturan $pengaturan,
    ) {}

    /** Tandai respons untuk dicatat sebagai kunjungan (oleh middleware CatatKunjunganPublik). */
    protected function tandai(Request $request, string $jenis, ?int $babId = null, ?int $halamanId = null): void
    {
        $request->attributes->set('statistik', ['jenis' => $jenis, 'bab_id' => $babId, 'halaman_id' => $halamanId]);
    }

    protected function pemilikPublik(): ?Pengguna
    {
        return Pengguna::query()->with('fotoProfil')->where('status', 'aktif')->where('tampilkan_profil_publik', true)->orderBy('id')->first();
    }
}
