<?php

namespace App\Http\Resources;

use App\Models\AspekKarakter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AspekKarakter */
class AspekPublikResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode_aspek,
            'nama' => $this->nama,
            'slug' => $this->slug,
            'deskripsi' => $this->deskripsi,
            'contoh_pembiasaan' => $this->contoh_pembiasaan,
            'fokus_pengamatan' => $this->fokus_pengamatan,
            'ikon' => $this->ikon,
            'warna' => $this->warna,
            'indikator' => $this->relationLoaded('indikator') ? $this->indikator->where('aktif', true)->values()->map(fn ($i) => [
                'nomor' => $i->nomor_indikator,
                'rumusan' => $i->rumusan,
                'contoh_pengamatan' => $i->contoh_pengamatan,
                'fase_pengamatan' => $i->fase_pengamatan ?? [],
                'catatan_pendampingan' => $i->catatan_pendampingan,
                'rubrik' => ['BB' => $i->rubrik_bb, 'MB' => $i->rubrik_mb, 'BSH' => $i->rubrik_bsh, 'BSB' => $i->rubrik_bsb],
            ])->all() : [],
        ];
    }
}
