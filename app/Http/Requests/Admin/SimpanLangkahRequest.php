<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SimpanLangkahRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:150'],
            'penjelasan' => ['required', 'string', 'max:5000'],
            'aktivitas_guru' => ['nullable', 'string', 'max:3000'],
            'aktivitas_anak' => ['nullable', 'string', 'max:3000'],
            'contoh_dialog' => ['nullable', 'array', 'max:20'],
            'contoh_dialog.*.pembicara' => ['required', 'string', 'max:40'],
            'contoh_dialog.*.ucapan' => ['required', 'string', 'max:500'],
            'media_id' => ['nullable', 'integer', Rule::exists('berkas_media', 'id')],
        ];
    }
}
