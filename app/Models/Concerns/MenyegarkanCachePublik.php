<?php

namespace App\Models\Concerns;

use App\Layanan\LayananPengaturan;

/**
 * Setiap perubahan model konten publik menaikkan versi cache publik sehingga navigasi, beranda,
 * sitemap, dan izin media publik dibangun ulang.
 */
trait MenyegarkanCachePublik
{
    public static function bootMenyegarkanCachePublik(): void
    {
        $segarkan = fn () => app(LayananPengaturan::class)->naikkanVersiKonten();
        static::saved($segarkan);
        static::deleted($segarkan);
    }
}
