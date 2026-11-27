<?php

namespace App\Http\Resources;

use App\Layanan\LayananWaktuBaca;
use App\Models\BabModul;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BabModul */
class BabRingkasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $kata = LayananWaktuBaca::jumlahKata((string) $this->pengantar, ...$this->subbabTerbit->map(fn ($s) => $s->isi.' '.$s->contoh_praktik)->all());

        return [
            'id' => $this->id,
            'nomor' => $this->nomor_bab,
            'romawi' => $this->nomor_romawi,
            'judul' => $this->judul,
            'slug' => $this->slug,
            'url' => '/modul/'.$this->slug,
            'ringkasan' => $this->ringkasan,
            'jumlah_subbab' => $this->subbabTerbit->count(),
            'waktu_baca_menit' => LayananWaktuBaca::menit($kata),
            'ada_dokumen' => (bool) $this->versiAktif,
            'sampul' => $this->gambarSampul ? (new MediaPublikResource($this->gambarSampul))->resolve($request) : null,
            'diperbarui_pada' => $this->diperbarui_pada?->toIso8601String(),
        ];
    }
}
