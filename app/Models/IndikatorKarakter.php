<?php

namespace App\Models;

use App\Models\Concerns\MenyegarkanCachePublik;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Data referensi materi (16 indikator). Bukan penyimpanan hasil penilaian anak. */
class IndikatorKarakter extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'indikator_karakter';

    protected $fillable = [
        'rumusan', 'contoh_pengamatan', 'fase_pengamatan', 'catatan_pendampingan',
        'rubrik_bb', 'rubrik_mb', 'rubrik_bsh', 'rubrik_bsb', 'aktif',
    ];

    protected function casts(): array
    {
        return ['fase_pengamatan' => 'array', 'aktif' => 'boolean'];
    }

    public function aspek(): BelongsTo
    {
        return $this->belongsTo(AspekKarakter::class, 'aspek_id');
    }
}
