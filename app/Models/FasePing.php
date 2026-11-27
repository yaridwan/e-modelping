<?php

namespace App\Models;

use App\Enums\StatusKonten;
use App\Models\Concerns\MenyegarkanCachePublik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FasePing extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'fase_ping';

    protected $fillable = [
        'nama', 'slug', 'judul_indonesia', 'deskripsi', 'tujuan', 'waktu_pelaksanaan',
        'durasi_minimum_menit', 'durasi_maksimum_menit', 'frekuensi_panduan', 'media_id',
        'catatan_guru', 'indikator_keberhasilan', 'penguatan_karakter', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusKonten::class,
            'tujuan' => 'array',
            'indikator_keberhasilan' => 'array',
            'penguatan_karakter' => 'array',
        ];
    }

    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('status', StatusKonten::Terbit);
    }

    public function langkah(): HasMany
    {
        return $this->hasMany(LangkahFase::class, 'fase_id')->orderBy('urutan');
    }

    public function bab(): BelongsTo
    {
        return $this->belongsTo(BabModul::class, 'bab_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'media_id');
    }

    public function teksDurasi(): string
    {
        $min = $this->durasi_minimum_menit;
        $maks = $this->durasi_maksimum_menit;
        if ($min && $maks && $min !== $maks) {
            return "{$min}–{$maks} menit";
        }

        return ($min ?: $maks) ? ($min ?: $maks).' menit' : '-';
    }
}
