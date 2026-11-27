<?php

namespace App\Models;

use App\Models\Concerns\MenyegarkanCachePublik;
use Illuminate\Database\Eloquent\Builder;

class PertanyaanUmum extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'pertanyaan_umum';

    protected $fillable = ['kategori', 'pertanyaan', 'jawaban', 'urutan', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }
}
