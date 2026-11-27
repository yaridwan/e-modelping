<?php

namespace App\Models;

use App\Models\Concerns\MenyegarkanCachePublik;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LangkahFase extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'langkah_fase';

    protected $fillable = ['nomor_langkah', 'judul', 'penjelasan', 'aktivitas_guru', 'aktivitas_anak', 'contoh_dialog', 'media_id', 'urutan'];

    protected function casts(): array
    {
        return ['contoh_dialog' => 'array'];
    }

    public function fase(): BelongsTo
    {
        return $this->belongsTo(FasePing::class, 'fase_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'media_id');
    }
}
