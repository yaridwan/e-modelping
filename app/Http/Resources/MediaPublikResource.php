<?php

namespace App\Http\Resources;

use App\Enums\JenisMedia;
use App\Layanan\LayananMedia;
use App\Models\BerkasMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BerkasMedia */
class MediaPublikResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'jenis' => $this->jenis_media->value,
            'url' => $this->jenis_media === JenisMedia::VideoEksternal ? LayananMedia::urlEmbedVideo($this->url_eksternal) : $this->urlPublik(),
            'tautan_asli' => $this->jenis_media === JenisMedia::VideoEksternal ? $this->url_eksternal : null,
            'judul' => $this->judul,
            'alt' => $this->teks_alternatif ?: $this->judul,
            'lebar' => $this->lebar_piksel,
            'tinggi' => $this->tinggi_piksel,
            'keterangan' => $this->keterangan,
            'sumber' => $this->sumber,
            'lisensi' => $this->lisensi,
        ];
    }
}
