<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SimpanProfilRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nama_panggilan' => ['nullable', 'string', 'max:60'],
            'nomor_telepon' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 \-]{8,20}$/'],
            'jabatan' => ['nullable', 'string', 'max:120'],
            'institusi' => ['nullable', 'string', 'max:150'],
            'bidang_keahlian' => ['nullable', 'string', 'max:191'],
            'biografi' => ['nullable', 'string', 'max:3000'],
            'situs_pribadi' => ['nullable', 'string', 'max:255', 'url:https,http'],
            'surel_publik' => ['nullable', 'email', 'max:191'],
            'nomor_kontak_publik' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 \-]{8,20}$/'],
            'tampilkan_profil_publik' => ['boolean'],
            'tampilkan_foto_publik' => ['boolean'],
            'tampilkan_kontak_publik' => ['boolean'],
            'tema_pilihan' => ['required', Rule::in(['terang', 'gelap', 'sistem'])],
        ];
    }

    public function messages(): array
    {
        return ['nomor_telepon.regex' => 'Nomor telepon hanya berisi angka, spasi, tanda hubung, dan awalan +.', 'nomor_kontak_publik.regex' => 'Nomor kontak hanya berisi angka, spasi, tanda hubung, dan awalan +.'];
    }
}
