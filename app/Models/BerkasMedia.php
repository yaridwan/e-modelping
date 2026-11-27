<?php

namespace App\Models;

use App\Enums\JenisMedia;
use App\Models\Concerns\MenyegarkanCachePublik;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BerkasMedia extends ModelDasar
{
    use MenyegarkanCachePublik;

    protected $table = 'berkas_media';

    protected $fillable = ['judul', 'teks_alternatif', 'keterangan', 'sumber', 'lisensi'];

    protected function casts(): array
    {
        return [
            'jenis_media' => JenisMedia::class,
            'ukuran_bita' => 'integer',
            'lebar_piksel' => 'integer',
            'tinggi_piksel' => 'integer',
            'jumlah_halaman' => 'integer',
        ];
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diunggah_oleh');
    }

    public function urlPublik(): ?string
    {
        if ($this->jenis_media === JenisMedia::VideoEksternal) {
            return $this->url_eksternal;
        }

        if ($this->jenis_media === JenisMedia::Gambar) {
            return '/media/'.$this->uuid.'.'.($this->ekstensi ?: 'jpg');
        }

        return null;
    }

    public function urlAdmin(): ?string
    {
        return $this->jenis_media === JenisMedia::VideoEksternal ? $this->url_eksternal : '/admin/media/'.$this->id.'/berkas';
    }
}
