<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AktivitasDokumen extends ModelDasar
{
    public const UPDATED_AT = null;

    protected $table = 'aktivitas_dokumen';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tanggal_aktivitas' => 'date', 'data_contoh' => 'boolean'];
    }

    public function versi(): BelongsTo
    {
        return $this->belongsTo(VersiDokumen::class, 'versi_dokumen_id');
    }
}
