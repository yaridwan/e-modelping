<?php

namespace App\Models;

use App\Models\Concerns\MenyegarkanCachePublik;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuNavigasi extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'menu_navigasi';

    protected $fillable = ['induk_id', 'lokasi', 'label', 'jenis_tautan', 'halaman_id', 'bab_id', 'url', 'ikon', 'buka_tab_baru', 'urutan', 'aktif'];

    protected function casts(): array
    {
        return ['buka_tab_baru' => 'boolean', 'aktif' => 'boolean'];
    }

    public function induk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'induk_id');
    }

    public function anak(): HasMany
    {
        return $this->hasMany(self::class, 'induk_id')->orderBy('urutan');
    }

    public function halaman(): BelongsTo
    {
        return $this->belongsTo(HalamanInformasi::class, 'halaman_id');
    }

    public function bab(): BelongsTo
    {
        return $this->belongsTo(BabModul::class, 'bab_id');
    }
}
