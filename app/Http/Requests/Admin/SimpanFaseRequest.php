<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SimpanFaseRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:120'],
            'judul_indonesia' => ['required', 'string', 'max:150'],
            'deskripsi' => ['required', 'string', 'max:5000'],
            'tujuan' => ['nullable', 'array', 'max:15'],
            'tujuan.*' => ['required', 'string', 'max:300'],
            'waktu_pelaksanaan' => ['nullable', 'string', 'max:2000'],
            'durasi_minimum_menit' => ['nullable', 'integer', 'min:1', 'max:240'],
            'durasi_maksimum_menit' => ['nullable', 'integer', 'min:1', 'max:240', 'gte:durasi_minimum_menit'],
            'frekuensi_panduan' => ['nullable', 'string', 'max:191'],
            'media_id' => ['nullable', 'integer', Rule::exists('berkas_media', 'id')],
            'catatan_guru' => ['nullable', 'string', 'max:5000'],
            'indikator_keberhasilan' => ['nullable', 'array', 'max:15'],
            'indikator_keberhasilan.*' => ['required', 'string', 'max:300'],
            'penguatan_karakter' => ['nullable', 'array', 'max:8'],
            'penguatan_karakter.*.aspek' => ['required', 'string', 'max:80'],
            'penguatan_karakter.*.bentuk' => ['required', 'string', 'max:300'],
            'status' => ['required', Rule::in(['draf', 'terbit', 'arsip'])],
        ];
    }

    public function messages(): array
    {
        return ['durasi_maksimum_menit.gte' => 'Durasi maksimum tidak boleh lebih kecil dari durasi minimum.'];
    }
}
