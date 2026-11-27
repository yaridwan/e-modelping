<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenAturUlangSandi extends ModelDasar
{
    public const UPDATED_AT = null;

    protected $table = 'token_atur_ulang_sandi';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['kedaluwarsa_pada' => 'datetime', 'digunakan_pada' => 'datetime'];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }
}
