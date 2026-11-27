<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Layanan\LayananPengaturan;
use App\Layanan\LayananSeo;
use App\Models\BabModul;
use App\Models\FasePing;
use App\Models\GaleriKegiatan;
use App\Models\HalamanInformasi;
use App\Models\PertanyaanUmum;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /** Sitemap hanya memuat URL kanonis terbit; tanpa admin, pencarian, pratinjau, atau token. */
    public function sitemap(LayananPengaturan $pengaturan): Response
    {
        $xml = $pengaturan->ingat('sitemap', function () {
            $dasar = LayananSeo::urlDasar();
            $url = [];
            $tambah = function (string $jalur, $lastmod = null, string $prioritas = '0.6') use (&$url, $dasar) {
                $url[] = ['loc' => $dasar.$jalur, 'lastmod' => $lastmod?->toAtomString(), 'priority' => $prioritas];
            };

            $babTerakhir = BabModul::query()->terbit()->max('diperbarui_pada');
            $tambah('/', $babTerakhir ? CarbonImmutable::parse($babTerakhir) : null, '1.0');
            $tambah('/modul', $babTerakhir ? CarbonImmutable::parse($babTerakhir) : null, '0.9');
            foreach (BabModul::query()->terbit()->with('subbabTerbit:id,bab_id,diperbarui_pada')->orderBy('urutan')->get(['id', 'slug', 'diperbarui_pada']) as $b) {
                $lastmod = collect([$b->diperbarui_pada, ...$b->subbabTerbit->pluck('diperbarui_pada')->all()])->filter()->max();
                $tambah('/modul/'.$b->slug, $lastmod, '0.8');
            }
            foreach (FasePing::query()->terbit()->orderBy('urutan')->get(['slug', 'diperbarui_pada']) as $f) {
                $tambah('/fase/'.$f->slug, $f->diperbarui_pada, '0.7');
            }
            $tambah('/karakter', null, '0.7');
            foreach (HalamanInformasi::query()->terbit()->get(['kode_halaman', 'slug', 'diperbarui_pada']) as $h) {
                $jalur = match ($h->kode_halaman) {
                    'tentang' => '/tentang',
                    'panduan-penggunaan' => '/panduan-penggunaan',
                    'kebijakan-privasi' => '/kebijakan-privasi',
                    'kontak' => '/kontak',
                    'pertanyaan-umum' => PertanyaanUmum::query()->aktif()->exists() ? '/pertanyaan-umum' : null,
                    'galeri' => GaleriKegiatan::query()->terbit()->exists() ? '/galeri' : null,
                    default => '/'.$h->slug,
                };
                if ($jalur) {
                    $tambah($jalur, $h->diperbarui_pada, '0.5');
                }
            }

            $isi = collect($url)->map(fn ($u) => '  <url><loc>'.e($u['loc']).'</loc>'.($u['lastmod'] ? '<lastmod>'.$u['lastmod'].'</lastmod>' : '').'<priority>'.$u['priority'].'</priority></url>')->implode("\n");

            return '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n".$isi."\n".'</urlset>'."\n";
        }, 3600);

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** robots.txt bukan pengganti autentikasi; hanya arahan crawler. */
    public function robots(): Response
    {
        $isi = app()->isProduction()
            ? "User-agent: *\nAllow: /\nDisallow: /admin\n\nSitemap: ".LayananSeo::urlDasar()."/sitemap.xml\n"
            : "User-agent: *\nDisallow: /\n";

        return response($isi, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
