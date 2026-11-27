<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusPesan;
use Illuminate\Validation\Rule;

class PerbaruiPesanRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(StatusPesan::nilai())],
            'catatan_admin' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
