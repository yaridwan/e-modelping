<?php

namespace App\Http\Resources;

use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hanya informasi yang dipilih pemilik untuk publik. Surel akun, nomor telepon pribadi,
 * dan data keamanan tidak pernah disertakan.
 *
 * @mixin Pengguna
 */
class ProfilPenyusunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'nama' => $this->nama_lengkap,
            'inisial' => $this->inisial(),
            'jabatan' => $this->jabatan,
            'institusi' => $this->institusi,
            'bidang_keahlian' => $this->bidang_keahlian,
            'biografi' => $this->biografi,
            'situs' => $this->situs_pribadi,
            'foto' => $this->tampilkan_foto_publik && $this->fotoProfil ? (new MediaPublikResource($this->fotoProfil))->resolve($request) : null,
            'surel_publik' => $this->tampilkan_kontak_publik ? $this->surel_publik : null,
            'kontak_publik' => $this->tampilkan_kontak_publik ? $this->nomor_kontak_publik : null,
        ];
    }
}
