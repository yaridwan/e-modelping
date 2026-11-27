<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusPesan;
use Illuminate\Validation\Rule;

/** Aksi massal pesan masuk: ubah status atau hapus permanen (maks. 100 pesan per aksi). */
class AksiMassalPesanRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'array', 'min:1', 'max:100'],
            'id.*' => ['integer', 'distinct', 'min:1'],
            'aksi' => ['required', Rule::in([...StatusPesan::nilai(), 'hapus'])],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Pilih minimal satu pesan.',
            'id.max' => 'Maksimal 100 pesan per aksi.',
            'aksi.in' => 'Aksi tidak dikenal.',
        ];
    }
}
