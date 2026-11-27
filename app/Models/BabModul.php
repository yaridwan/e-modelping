<?php

namespace App\Models;

use App\Enums\StatusKonten;
use App\Models\Concerns\MenyegarkanCachePublik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BabModul extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'bab_modul';

    protected $fillable = [
        'judul', 'slug', 'ringkasan', 'pengantar', 'tujuan_pembelajaran', 'poin_penting',
        'gambar_sampul_id', 'judul_seo', 'deskripsi_seo', 'gambar_og_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusKonten::class,
            'tujuan_pembelajaran' => 'array',
            'poin_penting' => 'array',
            'metadata_sumber' => 'array',
            'diterbitkan_pada' => 'datetime',
        ];
    }

    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('status', StatusKonten::Terbit);
    }

    public function subbab(): HasMany
    {
        return $this->hasMany(SubbabModul::class, 'bab_id')->orderBy('urutan');
    }

    public function subbabTerbit(): HasMany
    {
        return $this->subbab()->where('status', StatusKonten::Terbit);
    }

    public function versiDokumen(): HasMany
    {
        return $this->hasMany(VersiDokumen::class, 'bab_id');
    }

    public function versiAktif(): HasOne
    {
        return $this->hasOne(VersiDokumen::class, 'bab_id')
            ->where('jenis_dokumen', 'bab')
            ->where('versi_aktif', true)
            ->where('status', StatusKonten::Terbit);
    }

    public function fase(): HasMany
    {
        return $this->hasMany(FasePing::class, 'bab_id')->orderBy('urutan');
    }

    public function gambarSampul(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'gambar_sampul_id');
    }

    public function gambarOg(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'gambar_og_id');
    }

    public function terbit(): bool
    {
        return $this->status === StatusKonten::Terbit;
    }

    public function urlPublik(): string
    {
        return '/modul/'.$this->slug;
    }
}
