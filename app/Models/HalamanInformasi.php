<?php

namespace App\Models;

use App\Enums\StatusKonten;
use App\Models\Concerns\MenyegarkanCachePublik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HalamanInformasi extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'halaman_informasi';

    protected $fillable = [
        'judul', 'slug', 'ringkasan', 'isi', 'gambar_sampul_id', 'judul_seo', 'deskripsi_seo',
        'gambar_og_id', 'status', 'tampilkan_di_navigasi',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusKonten::class,
            'diterbitkan_pada' => 'datetime',
            'tampilkan_di_navigasi' => 'boolean',
            'halaman_inti' => 'boolean',
        ];
    }

    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('status', StatusKonten::Terbit);
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
