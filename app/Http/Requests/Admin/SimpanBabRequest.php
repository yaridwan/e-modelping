<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SimpanBabRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        $bab = $this->route('bab');

        return [
            'judul' => ['required', 'string', 'max:191'],
            'slug' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('bab_modul', 'slug')->ignore($bab?->id)],
            'ringkasan' => ['required', 'string', 'max:1000'],
            'pengantar' => ['nullable', 'string', 'max:200000'],
            'tujuan_pembelajaran' => ['nullable', 'array', 'max:15'],
            'tujuan_pembelajaran.*' => ['required', 'string', 'max:300'],
            'poin_penting' => ['nullable', 'array', 'max:15'],
            'poin_penting.*' => ['required', 'string', 'max:300'],
            'gambar_sampul_id' => ['nullable', 'integer', Rule::exists('berkas_media', 'id')->where('jenis_media', 'gambar')],
            'gambar_og_id' => ['nullable', 'integer', Rule::exists('berkas_media', 'id')->where('jenis_media', 'gambar')],
            'judul_seo' => ['nullable', 'string', 'max:191'],
            'deskripsi_seo' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return ['slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.'];
    }
}
