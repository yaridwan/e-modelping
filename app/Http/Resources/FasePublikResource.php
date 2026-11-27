<?php

namespace App\Http\Resources;

use App\Models\FasePing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FasePing */
class FasePublikResource extends JsonResource
{
    public bool $lengkap = false;

    public function lengkap(): static
    {
        $this->lengkap = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'kode' => $this->kode_fase,
            'nama' => $this->nama,
            'slug' => $this->slug,
            'url' => '/fase/'.$this->slug,
            'judul_indonesia' => $this->judul_indonesia,
            'deskripsi' => $this->deskripsi,
            'durasi' => $this->teksDurasi(),
            'durasi_minimum_menit' => $this->durasi_minimum_menit,
            'durasi_maksimum_menit' => $this->durasi_maksimum_menit,
            'frekuensi' => $this->frekuensi_panduan,
            'urutan' => $this->urutan,
            'jumlah_langkah' => $this->relationLoaded('langkah') ? $this->langkah->count() : null,
            'langkah_ringkas' => $this->relationLoaded('langkah') ? $this->langkah->pluck('judul')->all() : [],
        ];

        if ($this->lengkap) {
            $data += [
                'tujuan' => $this->tujuan ?? [],
                'waktu_pelaksanaan' => $this->waktu_pelaksanaan,
                'catatan_guru' => $this->catatan_guru,
                'indikator_keberhasilan' => $this->indikator_keberhasilan ?? [],
                'penguatan_karakter' => $this->penguatan_karakter ?? [],
                'media' => $this->media ? (new MediaPublikResource($this->media))->resolve($request) : null,
                'bab' => $this->bab && $this->bab->terbit() ? ['judul' => $this->bab->judul, 'romawi' => $this->bab->nomor_romawi, 'url' => '/modul/'.$this->bab->slug] : null,
                'langkah' => $this->langkah->map(fn ($l) => [
                    'id' => $l->id,
                    'nomor' => $l->nomor_langkah,
                    'judul' => $l->judul,
                    'penjelasan' => $l->penjelasan,
                    'aktivitas_guru' => $l->aktivitas_guru,
                    'aktivitas_anak' => $l->aktivitas_anak,
                    'contoh_dialog' => $l->contoh_dialog ?? [],
                    'media' => $l->media ? (new MediaPublikResource($l->media))->resolve($request) : null,
                ])->all(),
            ];
        }

        return $data;
    }
}
