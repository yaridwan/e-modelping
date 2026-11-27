<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SimpanSubbabRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        $bab = $this->route('bab');
        $subbab = $this->route('subbab');

        return [
            'kode_subbab' => ['required', 'string', 'max:20'],
            'judul' => ['required', 'string', 'max:191'],
            'slug' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('subbab_modul', 'slug')->where('bab_id', $bab->id)->ignore($subbab?->id)],
            'ringkasan' => ['nullable', 'string', 'max:1000'],
            'isi' => ['required', 'string', 'max:500000'],
            'contoh_praktik' => ['nullable', 'string', 'max:200000'],
            'media_id' => ['nullable', 'integer', Rule::exists('berkas_media', 'id')],
            'status' => ['required', Rule::in(['draf', 'terbit', 'arsip'])],
        ];
    }

    public function messages(): array
    {
        return ['slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.', 'slug.unique' => 'Slug sudah dipakai subbab lain pada bab ini.'];
    }
}
