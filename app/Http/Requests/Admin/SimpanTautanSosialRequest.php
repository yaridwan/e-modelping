<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SimpanTautanSosialRequest extends PermintaanAdmin
{
    public const PLATFORM = ['instagram', 'facebook', 'youtube', 'tiktok', 'x', 'linkedin', 'whatsapp', 'situs', 'lainnya'];

    public function rules(): array
    {
        return [
            'nama_platform' => ['required', Rule::in(self::PLATFORM)],
            'label' => ['required', 'string', 'max:80'],
            'url' => ['nullable', 'required_if:aktif,true', 'string', 'max:255', 'url:https,http'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
            'aktif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['url.required_if' => 'Tautan aktif wajib memiliki URL milik pemilik website.'];
    }
}
