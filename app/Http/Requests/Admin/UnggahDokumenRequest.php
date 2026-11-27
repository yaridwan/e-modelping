<?php

namespace App\Http\Requests\Admin;

use App\Layanan\LayananMedia;
use Illuminate\Validation\Rule;

class UnggahDokumenRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        $kb = app(LayananMedia::class)->batasMb() * 1024;

        return [
            'jenis_dokumen' => ['required', Rule::in(['bab', 'modul_lengkap'])],
            'bab_id' => ['nullable', 'required_if:jenis_dokumen,bab', 'prohibited_if:jenis_dokumen,modul_lengkap', 'integer', Rule::exists('bab_modul', 'id')],
            'nomor_versi' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z][0-9A-Za-z.\-]*$/'],
            'judul_dokumen' => ['required', 'string', 'max:191'],
            'catatan_perubahan' => ['nullable', 'string', 'max:2000'],
            'izinkan_unduh' => ['boolean'],
            'berkas' => ['required', 'file', 'max:'.$kb, 'mimes:pdf', 'mimetypes:application/pdf'],
            'terbitkan_sekarang' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['bab_id' => 'bab', 'berkas' => 'berkas PDF', 'nomor_versi' => 'nomor versi'];
    }

    public function messages(): array
    {
        return [
            'bab_id.required_if' => 'Pilih bab untuk dokumen bab.',
            'bab_id.prohibited_if' => 'Dokumen modul lengkap tidak boleh dikaitkan dengan bab.',
            'nomor_versi.regex' => 'Nomor versi hanya berisi huruf, angka, titik, atau tanda hubung (mis. 1.0, 2.1-revisi).',
            'berkas.max' => 'Ukuran PDF melebihi batas '.app(LayananMedia::class)->batasMb().' MB.',
            'berkas.mimes' => 'Berkas harus berformat PDF.',
            'berkas.mimetypes' => 'Isi berkas bukan PDF yang valid.',
        ];
    }
}
