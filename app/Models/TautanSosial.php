<?php

namespace App\Models;

use App\Models\Concerns\MenyegarkanCachePublik;

class TautanSosial extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'tautan_sosial';

    protected $fillable = ['nama_platform', 'label', 'url', 'ikon', 'urutan', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }
}
