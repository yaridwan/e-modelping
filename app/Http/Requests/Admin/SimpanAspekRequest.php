<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SimpanAspekRequest extends PermintaanAdmin
{
    public const IKON = ['list-checks', 'hand-heart', 'heart-pulse', 'messages-square', 'sparkles', 'smile', 'shield-check', 'star'];

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:80'],
            'deskripsi' => ['required', 'string', 'max:3000'],
            'contoh_pembiasaan' => ['nullable', 'string', 'max:2000'],
            'fokus_pengamatan' => ['nullable', 'string', 'max:191'],
            'ikon' => ['nullable', Rule::in(self::IKON)],
            'warna' => ['nullable', Rule::in(['pink', 'rose', 'fuchsia', 'violet', 'amber', 'emerald', 'sky'])],
            'aktif' => ['boolean'],
        ];
    }
}
