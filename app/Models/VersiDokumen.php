<?php

namespace App\Models;

use App\Enums\StatusKonten;
use App\Models\Concerns\MenyegarkanCachePublik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersiDokumen extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'versi_dokumen';

    protected $fillable = ['nomor_versi', 'judul_dokumen', 'catatan_perubahan', 'izinkan_unduh'];

    protected function casts(): array
    {
        return [
            'status' => StatusKonten::class,
            'versi_aktif' => 'boolean',
            'izinkan_unduh' => 'boolean',
            'diterbitkan_pada' => 'datetime',
        ];
    }

    public function bab(): BelongsTo
    {
        return $this->belongsTo(BabModul::class, 'bab_id');
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'berkas_id');
    }

    public function penerbit(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diterbitkan_oleh');
    }

    /** Versi yang boleh disajikan ke publik: terbit, aktif, dan (untuk dokumen bab) induk bab terbit. */
    public function scopeTersediaPublik(Builder $query): Builder
    {
        return $query->where('status', StatusKonten::Terbit)
            ->where('versi_aktif', true)
            ->where(function (Builder $q) {
                $q->where('jenis_dokumen', 'modul_lengkap')
                    ->orWhereHas('bab', fn (Builder $b) => $b->where('status', StatusKonten::Terbit));
            });
    }

    public function namaBerkasAman(): string
    {
        $dasar = $this->jenis_dokumen === 'modul_lengkap'
            ? 'e-model-ping-modul-lengkap'
            : 'e-model-ping-bab-'.($this->bab?->nomor_bab ?? 'x');
        $versi = preg_replace('/[^A-Za-z0-9.\-]/', '', $this->nomor_versi) ?: 'v';

        return $dasar.'-v'.ltrim($versi, 'vV').'.pdf';
    }
}
