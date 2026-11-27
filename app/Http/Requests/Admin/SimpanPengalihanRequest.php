<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SimpanPengalihanRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        $p = $this->route('pengalihan');

        return [
            'jalur_lama' => ['required', 'string', 'max:190', 'regex:#^/[A-Za-z0-9\-/._]*$#', Rule::unique('pengalihan_url', 'jalur_lama')->ignore($p?->id)],
            'jalur_baru' => ['required', 'string', 'max:190', 'regex:#^/[A-Za-z0-9\-/._]*$#'],
            'kode_status' => ['required', Rule::in([301, 302, 308])],
            'aktif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['jalur_lama.regex' => 'Gunakan jalur internal yang diawali "/".', 'jalur_baru.regex' => 'Gunakan jalur internal yang diawali "/".'];
    }
}
