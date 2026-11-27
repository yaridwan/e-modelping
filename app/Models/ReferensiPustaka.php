<?php

namespace App\Models;

use App\Models\Concerns\MenyegarkanCachePublik;

class ReferensiPustaka extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'referensi_pustaka';

    protected $fillable = [
        'penulis', 'tahun', 'judul', 'jenis_sumber', 'nama_penerbit_jurnal', 'volume', 'nomor_terbitan',
        'halaman', 'doi', 'url', 'tanggal_akses', 'catatan_editorial', 'status_verifikasi', 'urutan', 'aktif',
    ];

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'tanggal_akses' => 'date', 'tahun' => 'integer'];
    }
}
