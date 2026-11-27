<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SimpanGaleriRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        $galeri = $this->route('galeri');

        return [
            'judul' => ['required', 'string', 'max:191'],
            'slug' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('galeri_kegiatan', 'slug')->ignore($galeri?->id)],
            'ringkasan' => ['nullable', 'string', 'max:1000'],
            'media_id' => ['required', 'integer', Rule::exists('berkas_media', 'id')->where('jenis_media', 'gambar')],
            'bab_id' => ['nullable', 'integer', Rule::exists('bab_modul', 'id')],
            'fase_id' => ['nullable', 'integer', Rule::exists('fase_ping', 'id')],
            'tanggal_kegiatan' => ['nullable', 'date'],
            'lokasi_umum' => ['nullable', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:3000'],
            'teks_alternatif' => ['required', 'string', 'max:255'],
            'dasar_izin_publikasi' => ['nullable', 'string', 'max:2000'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function attributes(): array
    {
        return ['media_id' => 'gambar', 'teks_alternatif' => 'teks alternatif'];
    }
}
