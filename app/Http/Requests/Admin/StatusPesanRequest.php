<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusPesan;
use Illuminate\Validation\Rule;

/** Ubah status satu pesan tanpa menyentuh catatan internal. */
class StatusPesanRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(StatusPesan::nilai())],
        ];
    }
}
