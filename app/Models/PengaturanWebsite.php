<?php

namespace App\Models;

use App\Layanan\LayananPengaturan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaturanWebsite extends ModelDasar
{
    protected $table = 'pengaturan_website';

    protected $guarded = ['id', 'kunci_tunggal', 'dibuat_pada', 'diperbarui_pada'];

    protected static function booted(): void
    {
        // Identitas dipakai di header/footer, login, dashboard, metadata, dan surel: bersihkan cache.
        static::saved(fn () => app(LayananPengaturan::class)->bersihkan());
    }

    protected function casts(): array
    {
        return [
            'izinkan_unduh' => 'boolean',
            'statistik_aktif' => 'boolean',
            'kecualikan_admin' => 'boolean',
            'kecualikan_bot' => 'boolean',
            'batas_unggah_mb' => 'integer',
            'retensi_statistik_hari' => 'integer',
            'retensi_pesan_hari' => 'integer',
            'tahun_hak_cipta_awal' => 'integer',
        ];
    }

    public function logoTerang(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'logo_terang_id');
    }

    public function logoGelap(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'logo_gelap_id');
    }

    public function favicon(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'favicon_id');
    }

    public function gambarSampul(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'gambar_sampul_id');
    }

    public function gambarOg(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'gambar_og_id');
    }
}
