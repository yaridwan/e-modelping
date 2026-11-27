<?php

namespace App\Http\Requests\Admin;

use App\Support\SkemaBagianBeranda;
use Illuminate\Validation\Rule;

class SimpanBagianBerandaRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        $bagian = $this->route('bagian');

        return [
            'judul' => ['required', 'string', 'max:191'],
            'subjudul' => ['nullable', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string', 'max:1500'],
            'isi' => ['nullable', 'string', 'max:5000'],
            'teks_tombol_utama' => ['nullable', 'string', 'max:60', 'required_with:tautan_tombol_utama'],
            'tautan_tombol_utama' => ['nullable', 'string', 'max:255', 'required_with:teks_tombol_utama', $this->urlAman()],
            'teks_tombol_pendamping' => ['nullable', 'string', 'max:60', 'required_with:tautan_tombol_pendamping'],
            'tautan_tombol_pendamping' => ['nullable', 'string', 'max:255', 'required_with:teks_tombol_pendamping', $this->urlAman()],
            'media_id' => ['nullable', 'integer', Rule::exists('berkas_media', 'id')->where('jenis_media', 'gambar')],
            'aktif' => ['boolean'],
            'pengaturan_tampilan' => ['nullable', 'array'],
            ...SkemaBagianBeranda::aturan((string) $bagian?->jenis_bagian),
        ];
    }
}
