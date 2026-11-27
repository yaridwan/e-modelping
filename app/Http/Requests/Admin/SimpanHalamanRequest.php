<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SimpanHalamanRequest extends PermintaanAdmin
{
    public const SLUG_TERCADANG = ['admin', 'modul', 'fase', 'karakter', 'dokumen', 'media', 'cari', 'sitemap', 'robots', 'build', 'storage', 'up', 'api', 'tentang', 'galeri', 'kontak', 'pertanyaan-umum', 'panduan-penggunaan', 'kebijakan-privasi'];

    public function rules(): array
    {
        $halaman = $this->route('halaman');
        $inti = (bool) $halaman?->halaman_inti;

        return [
            'judul' => ['required', 'string', 'max:191'],
            'slug' => $inti ? ['prohibited'] : ['required', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn(self::SLUG_TERCADANG), Rule::unique('halaman_informasi', 'slug')->ignore($halaman?->id)],
            'ringkasan' => ['nullable', 'string', 'max:1000'],
            'isi' => ['nullable', 'string', 'max:500000'],
            'gambar_sampul_id' => ['nullable', 'integer', Rule::exists('berkas_media', 'id')->where('jenis_media', 'gambar')],
            'gambar_og_id' => ['nullable', 'integer', Rule::exists('berkas_media', 'id')->where('jenis_media', 'gambar')],
            'judul_seo' => ['nullable', 'string', 'max:191'],
            'deskripsi_seo' => ['nullable', 'string', 'max:300'],
            'tampilkan_di_navigasi' => ['boolean'],
            'status' => ['required', Rule::in(['draf', 'terbit', 'arsip'])],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'slug.not_in' => 'Slug ini dipakai sistem. Pilih slug lain.',
            'slug.prohibited' => 'Slug halaman inti tidak dapat diubah.',
        ];
    }
}
