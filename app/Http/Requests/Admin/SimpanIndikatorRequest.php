<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SimpanIndikatorRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        return [
            'rumusan' => ['required', 'string', 'max:255'],
            'contoh_pengamatan' => ['nullable', 'string', 'max:3000'],
            'fase_pengamatan' => ['nullable', 'array'],
            'fase_pengamatan.*' => [Rule::in(['PCG', 'ICTG', 'EDCG'])],
            'catatan_pendampingan' => ['nullable', 'string', 'max:3000'],
            'rubrik_bb' => ['nullable', 'string', 'max:1000'],
            'rubrik_mb' => ['nullable', 'string', 'max:1000'],
            'rubrik_bsh' => ['nullable', 'string', 'max:1000'],
            'rubrik_bsb' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['boolean'],
        ];
    }
}
