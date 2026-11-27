<?php

namespace App\Http\Requests\Admin;

class SimpanPertanyaanRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        return [
            'kategori' => ['required', 'string', 'max:60'],
            'pertanyaan' => ['required', 'string', 'max:255'],
            'jawaban' => ['required', 'string', 'max:5000'],
            'aktif' => ['boolean'],
        ];
    }
}
