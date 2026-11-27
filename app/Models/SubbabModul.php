<?php

namespace App\Models;

use App\Enums\StatusKonten;
use App\Models\Concerns\MenyegarkanCachePublik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubbabModul extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'subbab_modul';

    protected $fillable = ['kode_subbab', 'judul', 'slug', 'ringkasan', 'isi', 'contoh_praktik', 'media_id', 'status'];

    protected function casts(): array
    {
        return ['status' => StatusKonten::class, 'metadata_sumber' => 'array'];
    }

    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('status', StatusKonten::Terbit);
    }

    public function bab(): BelongsTo
    {
        return $this->belongsTo(BabModul::class, 'bab_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'media_id');
    }
}
