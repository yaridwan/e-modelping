<?php

namespace App\Layanan;

use App\Models\BerkasMedia;
use App\Models\PengaturanWebsite;
use Illuminate\Support\Facades\Cache;

/**
 * Akses terpusat ke pengaturan website (singleton) dengan cache dan versi konten publik.
 */
class LayananPengaturan
{
    private const KUNCI_CACHE = 'ping.pengaturan';

    private const KUNCI_VERSI = 'ping.versi_konten';

    private ?PengaturanWebsite $memo = null;

    public function ambil(): PengaturanWebsite
    {
        if ($this->memo) {
            return $this->memo;
        }

        $data = Cache::rememberForever(self::KUNCI_CACHE, function () {
            $pengaturan = PengaturanWebsite::query()
                ->with(['logoTerang', 'logoGelap', 'favicon', 'gambarSampul', 'gambarOg'])
                ->where('kunci_tunggal', 'utama')
                ->first();

            return $pengaturan?->toArray();
        });

        if (! $data) {
            return $this->memo = $this->bawaan();
        }

        $model = (new PengaturanWebsite)->newFromBuilder(collect($data)->except(['logo_terang', 'logo_gelap', 'favicon', 'gambar_sampul', 'gambar_og'])->all());
        foreach (['logo_terang' => 'logoTerang', 'logo_gelap' => 'logoGelap', 'favicon' => 'favicon', 'gambar_sampul' => 'gambarSampul', 'gambar_og' => 'gambarOg'] as $kunci => $relasi) {
            $model->setRelation($relasi, isset($data[$kunci]) ? (new BerkasMedia)->newFromBuilder($data[$kunci]) : null);
        }

        return $this->memo = $model;
    }

    public function zonaWaktu(): string
    {
        return $this->ambil()->zona_waktu ?: 'Asia/Jakarta';
    }

    public function bersihkan(): void
    {
        $this->memo = null;
        Cache::forget(self::KUNCI_CACHE);
        $this->naikkanVersiKonten();
    }

    /** Versi konten publik; dipakai sebagai bagian kunci cache publik (sitemap, navigasi, beranda). */
    public function versiKonten(): int
    {
        return (int) Cache::rememberForever(self::KUNCI_VERSI, fn () => 1);
    }

    public function naikkanVersiKonten(): void
    {
        Cache::forever(self::KUNCI_VERSI, $this->versiKonten() + 1);
    }

    /** Cache bertingkat yang otomatis tidak berlaku saat konten berubah. */
    public function ingat(string $kunci, \Closure $hasil, int $detik = 3600): mixed
    {
        return Cache::remember("ping.publik.{$this->versiKonten()}.{$kunci}", $detik, $hasil);
    }

    private function bawaan(): PengaturanWebsite
    {
        $p = new PengaturanWebsite;
        $p->forceFill([
            'nama_aplikasi' => config('app.name', 'E-Model PING'),
            'nama_singkat' => 'PING',
            'tagline' => 'Model Pembiasaan Karakter Anak Usia Dini',
            'bahasa' => 'id',
            'zona_waktu' => 'Asia/Jakarta',
            'warna_utama' => '#BE185D',
            'warna_pendamping' => '#EC4899',
            'tema_bawaan' => 'sistem',
            'templat_judul_seo' => '%judul% | %nama%',
            'izinkan_unduh' => true,
            'batas_unggah_mb' => 25,
            'statistik_aktif' => false,
            'kecualikan_admin' => true,
            'kecualikan_bot' => true,
            'retensi_statistik_hari' => 365,
            'retensi_pesan_hari' => 730,
            'jenis_identitas_penerbit' => 'orang',
        ]);
        foreach (['logoTerang', 'logoGelap', 'favicon', 'gambarSampul', 'gambarOg'] as $relasi) {
            $p->setRelation($relasi, null);
        }

        return $p;
    }
}
