<?php

namespace App\Models;

use App\Enums\StatusKonten;
use App\Models\Concerns\MenyegarkanCachePublik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GaleriKegiatan extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'galeri_kegiatan';

    protected $fillable = [
        'judul', 'slug', 'ringkasan', 'media_id', 'bab_id', 'fase_id', 'tanggal_kegiatan', 'lokasi_umum',
        'keterangan', 'teks_alternatif', 'dasar_izin_publikasi', 'urutan',
    ];

    protected function casts(): array
    {
        return ['status' => StatusKonten::class, 'tanggal_kegiatan' => 'date', 'diterbitkan_pada' => 'datetime'];
    }

    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('status', StatusKonten::Terbit);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'media_id');
    }

    public function bab(): BelongsTo
    {
        return $this->belongsTo(BabModul::class, 'bab_id');
    }

    public function fase(): BelongsTo
    {
        return $this->belongsTo(FasePing::class, 'fase_id');
    }
}
