<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends ModelDasar
{
    public const UPDATED_AT = null;

    protected $table = 'log_aktivitas';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['perubahan' => 'array', 'data_contoh' => 'boolean'];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }
}
