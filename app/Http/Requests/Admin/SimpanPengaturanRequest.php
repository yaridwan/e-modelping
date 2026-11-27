<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SimpanPengaturanRequest extends PermintaanAdmin
{
    public function rules(): array
    {
        $gambar = ['nullable', 'file', 'max:4096', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp'];
        $hex = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return match ($this->route('tab')) {
            'identitas' => [
                'nama_aplikasi' => ['required', 'string', 'max:120'],
                'nama_singkat' => ['required', 'string', 'max:40'],
                'tagline' => ['nullable', 'string', 'max:191'],
                'deskripsi_singkat' => ['nullable', 'string', 'max:1000'],
                'nama_pemilik' => ['nullable', 'string', 'max:150'],
                'nama_institusi' => ['nullable', 'string', 'max:150'],
                'zona_waktu' => ['required', Rule::in(['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'])],
                'logo_terang' => $gambar, 'logo_gelap' => $gambar, 'favicon' => ['nullable', 'file', 'max:1024', 'mimes:png', 'mimetypes:image/png'],
                'gambar_sampul' => $gambar,
                'hapus_logo_terang' => ['boolean'], 'hapus_logo_gelap' => ['boolean'], 'hapus_favicon' => ['boolean'], 'hapus_gambar_sampul' => ['boolean'],
            ],
            'kontak' => [
                'surel_kontak' => ['nullable', 'email', 'max:191'],
                'nomor_whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 \-]{8,20}$/'],
                'alamat_publik' => ['nullable', 'string', 'max:255'],
                'kota_kabupaten' => ['nullable', 'string', 'max:100'],
                'provinsi' => ['nullable', 'string', 'max:100'],
            ],
            'tampilan' => [
                'warna_utama' => $hex,
                'warna_pendamping' => $hex,
                'tema_bawaan' => ['required', Rule::in(['terang', 'gelap', 'sistem'])],
                'teks_footer' => ['nullable', 'string', 'max:500'],
                'tahun_hak_cipta_awal' => ['nullable', 'integer', 'min:2000', 'max:'.((int) date('Y') + 1)],
            ],
            'seo' => [
                'judul_seo_bawaan' => ['nullable', 'string', 'max:191'],
                'templat_judul_seo' => ['required', 'string', 'max:100', 'regex:/%judul%/'],
                'deskripsi_seo_bawaan' => ['nullable', 'string', 'max:300'],
                'jenis_identitas_penerbit' => ['required', Rule::in(['orang', 'organisasi'])],
                'kode_verifikasi_google' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\-]+$/'],
                'kode_verifikasi_bing' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\-]+$/'],
                'gambar_og' => $gambar,
                'hapus_gambar_og' => ['boolean'],
            ],
            'dokumen' => [
                'izinkan_unduh' => ['boolean'],
                'batas_unggah_mb' => ['required', 'integer', 'min:1', 'max:'.(int) config('ping.batas_unggah_maksimum_mb', 25)],
                'panduan_penamaan_versi' => ['nullable', 'string', 'max:2000'],
            ],
            'privasi' => [
                'statistik_aktif' => ['boolean'],
                'kecualikan_admin' => ['boolean'],
                'kecualikan_bot' => ['boolean'],
                'retensi_statistik_hari' => ['required', 'integer', 'min:30', 'max:1825'],
                'retensi_pesan_hari' => ['required', 'integer', 'min:30', 'max:3650'],
            ],
            default => [],
        };
    }

    public function messages(): array
    {
        return [
            'warna_utama.regex' => 'Warna harus berformat heksadesimal, contoh #BE185D.',
            'warna_pendamping.regex' => 'Warna harus berformat heksadesimal, contoh #EC4899.',
            'templat_judul_seo.regex' => 'Templat judul wajib memuat %judul%.',
            'nomor_whatsapp.regex' => 'Nomor WhatsApp hanya berisi angka, spasi, tanda hubung, dan awalan +.',
            'batas_unggah_mb.max' => 'Batas unggah tidak boleh melebihi batas server (:max MB).',
        ];
    }
}
