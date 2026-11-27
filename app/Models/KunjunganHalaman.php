<?php

namespace App\Models;

class KunjunganHalaman extends ModelDasar
{
    public const UPDATED_AT = null;

    protected $table = 'kunjungan_halaman';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tanggal_kunjungan' => 'date', 'data_contoh' => 'boolean'];
    }
}
