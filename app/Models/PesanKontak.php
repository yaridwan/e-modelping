<?php

namespace App\Models;

use App\Enums\StatusPesan;

class PesanKontak extends ModelDasar
{
    protected $table = 'pesan_kontak';

    protected $fillable = ['nama_pengirim', 'surel_pengirim', 'institusi', 'subjek', 'isi_pesan', 'persetujuan_privasi'];

    protected function casts(): array
    {
        return [
            'status' => StatusPesan::class,
            'persetujuan_privasi' => 'boolean',
            'data_contoh' => 'boolean',
            'dibaca_pada' => 'datetime',
            'ditindaklanjuti_pada' => 'datetime',
        ];
    }
}
