<?php

namespace App\Models;

use App\Models\Concerns\MenyegarkanCachePublik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AspekKarakter extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'aspek_karakter';

    protected $fillable = ['nama', 'deskripsi', 'contoh_pembiasaan', 'fokus_pengamatan', 'ikon', 'warna', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function indikator(): HasMany
    {
        return $this->hasMany(IndikatorKarakter::class, 'aspek_id')->orderBy('nomor_indikator');
    }
}
