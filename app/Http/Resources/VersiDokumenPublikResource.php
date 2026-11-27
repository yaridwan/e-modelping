<?php

namespace App\Http\Resources;

use App\Layanan\LayananDokumen;
use App\Models\VersiDokumen;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VersiDokumen */
class VersiDokumenPublikResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $bolehUnduh = app(LayananDokumen::class)->izinUnduhEfektif($this->resource);

        return [
            'id' => $this->id,
            'judul' => $this->judul_dokumen,
            'nomor_versi' => $this->nomor_versi,
            'ukuran_bita' => $this->berkas?->ukuran_bita,
            'jumlah_halaman' => $this->berkas?->jumlah_halaman,
            'diterbitkan_pada' => $this->diterbitkan_pada?->toIso8601String(),
            'url_baca' => '/dokumen/'.$this->id.'/baca',
            'url_berkas' => '/dokumen/'.$this->id.'/berkas',
            'url_unduh' => $bolehUnduh ? '/dokumen/'.$this->id.'/unduh' : null,
            'boleh_unduh' => $bolehUnduh,
        ];
    }
}
