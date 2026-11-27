<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SimpanReferensiRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        return [
            'penulis' => ['required', 'string', 'max:255'],
            'tahun' => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'judul' => ['required', 'string', 'max:500'],
            'jenis_sumber' => ['required', Rule::in(['buku', 'bab_buku', 'jurnal', 'laporan', 'laman', 'lainnya'])],
            'nama_penerbit_jurnal' => ['nullable', 'string', 'max:255'],
            'volume' => ['nullable', 'string', 'max:20'],
            'nomor_terbitan' => ['nullable', 'string', 'max:20'],
            'halaman' => ['nullable', 'string', 'max:40'],
            'doi' => ['nullable', 'string', 'max:191', 'regex:/^10\.\d{4,9}\/\S+$/'],
            'url' => ['nullable', 'string', 'max:500', 'url:http,https'],
            'tanggal_akses' => ['nullable', 'date'],
            'catatan_editorial' => ['nullable', 'string', 'max:2000'],
            'status_verifikasi' => ['required', Rule::in(['belum_diverifikasi', 'tercantum_di_sumber', 'terverifikasi', 'perlu_koreksi'])],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'aktif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['doi.regex' => 'Format DOI tidak valid (contoh: 10.1234/abcd). Kosongkan bila tidak diketahui; jangan mengarang DOI.'];
    }
}
