<?php

namespace App\Models;

use App\Models\Concerns\MenyegarkanCachePublik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BagianBeranda extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'bagian_beranda';

    protected $fillable = [
        'judul', 'subjudul', 'ringkasan', 'isi', 'teks_tombol_utama', 'tautan_tombol_utama',
        'teks_tombol_pendamping', 'tautan_tombol_pendamping', 'media_id', 'pengaturan_tampilan', 'aktif',
    ];

    protected function casts(): array
    {
        return ['pengaturan_tampilan' => 'array', 'aktif' => 'boolean'];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'media_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }
}
