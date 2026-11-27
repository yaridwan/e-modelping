<?php

namespace App\Http\Requests\Publik;

use Illuminate\Foundation\Http\FormRequest;

class KirimPesanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_pengirim' => ['required', 'string', 'min:2', 'max:120'],
            'surel_pengirim' => ['required', 'string', 'email:rfc', 'max:191'],
            'institusi' => ['nullable', 'string', 'max:150'],
            'subjek' => ['required', 'string', 'min:3', 'max:150'],
            'isi_pesan' => ['required', 'string', 'min:10', 'max:3000'],
            'persetujuan_privasi' => ['accepted'],
            // Honeypot: harus kosong. Bot yang mengisi kolom tersembunyi ditolak diam-diam.
            'situs_web' => ['nullable', 'max:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_pengirim' => 'nama',
            'surel_pengirim' => 'surel',
            'isi_pesan' => 'isi pesan',
            'persetujuan_privasi' => 'persetujuan kebijakan privasi',
        ];
    }

    public function messages(): array
    {
        return [
            'persetujuan_privasi.accepted' => 'Anda perlu menyetujui kebijakan privasi sebelum mengirim pesan.',
            'situs_web.max' => 'Pesan tidak dapat dikirim.',
        ];
    }
}
