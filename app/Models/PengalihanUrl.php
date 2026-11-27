<?php

namespace App\Models;

use App\Models\Concerns\MenyegarkanCachePublik;

class PengalihanUrl extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'pengalihan_url';

    protected $fillable = ['jalur_lama', 'jalur_baru', 'kode_status', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'kode_status' => 'integer'];
    }
}
