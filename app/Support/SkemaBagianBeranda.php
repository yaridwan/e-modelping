<?php

namespace App\Support;

/**
 * Struktur `pengaturan_tampilan` (JSON) per jenis bagian beranda. Admin mengisinya lewat
 * formulir sesuai jenis, bukan JSON mentah. Entitas (fase, bab, FAQ) tetap diambil dari
 * tabel masing-masing; JSON hanya menyimpan opsi tampilan.
 */
class SkemaBagianBeranda
{
    /** @return array<string, array{label: string, jenis: string, opsi?: array<string, string>, bawaan: mixed}> */
    public static function bidang(string $jenis): array
    {
        return match ($jenis) {
            'hero' => [
                'tampilkan_ilustrasi' => ['label' => 'Tampilkan ilustrasi animasi tiga fase', 'jenis' => 'boolean', 'bawaan' => true],
                'tampilkan_angka_struktur' => ['label' => 'Tampilkan angka struktur (9 bab, 3 fase, 4 aspek, 16 indikator)', 'jenis' => 'boolean', 'bawaan' => true],
            ],
            'pengantar' => [
                'manfaat' => ['label' => 'Daftar manfaat bagi guru (satu per baris)', 'jenis' => 'daftar', 'bawaan' => []],
            ],
            'fase' => [
                'tampilkan_durasi' => ['label' => 'Tampilkan durasi panduan', 'jenis' => 'boolean', 'bawaan' => true],
                'tampilkan_langkah' => ['label' => 'Tampilkan ringkasan langkah', 'jenis' => 'boolean', 'bawaan' => true],
            ],
            'aspek' => [
                'tampilkan_indikator' => ['label' => 'Tampilkan contoh indikator', 'jenis' => 'boolean', 'bawaan' => true],
            ],
            'bab' => [
                'tata_letak' => ['label' => 'Tata letak kartu bab', 'jenis' => 'pilihan', 'opsi' => ['grid' => 'Grid 3 kolom', 'daftar' => 'Daftar'], 'bawaan' => 'grid'],
                'tampilkan_status_dokumen' => ['label' => 'Tampilkan status ketersediaan dokumen', 'jenis' => 'boolean', 'bawaan' => true],
            ],
            'panduan' => [
                'langkah' => ['label' => 'Langkah penggunaan (format: Judul | penjelasan, satu per baris)', 'jenis' => 'daftar', 'bawaan' => []],
            ],
            'profil' => [
                'tampilkan_foto' => ['label' => 'Tampilkan foto bila diizinkan di profil', 'jenis' => 'boolean', 'bawaan' => true],
            ],
            'galeri' => [
                'jumlah_item' => ['label' => 'Jumlah gambar yang ditampilkan', 'jenis' => 'angka', 'bawaan' => 6],
            ],
            'faq' => [
                'jumlah_item' => ['label' => 'Jumlah pertanyaan yang ditampilkan', 'jenis' => 'angka', 'bawaan' => 5],
            ],
            'cta' => [
                'gaya' => ['label' => 'Gaya latar', 'jenis' => 'pilihan', 'opsi' => ['gradasi' => 'Gradasi pink', 'polos' => 'Polos lembut'], 'bawaan' => 'gradasi'],
            ],
            default => [],
        };
    }

    public static function aturan(string $jenis): array
    {
        $aturan = [];
        foreach (self::bidang($jenis) as $kunci => $def) {
            $k = 'pengaturan_tampilan.'.$kunci;
            $aturan[$k] = match ($def['jenis']) {
                'boolean' => ['nullable', 'boolean'],
                'angka' => ['nullable', 'integer', 'min:1', 'max:12'],
                'pilihan' => ['nullable', 'in:'.implode(',', array_keys($def['opsi'] ?? []))],
                'daftar' => ['nullable', 'array', 'max:10'],
                default => ['nullable'],
            };
            if ($def['jenis'] === 'daftar') {
                $aturan[$k.'.*'] = ['required', 'string', 'max:300'];
            }
        }

        return $aturan;
    }

    /** Saring input agar hanya kunci yang dikenal untuk jenis tersebut yang tersimpan. */
    public static function saring(string $jenis, ?array $masukan): array
    {
        $hasil = [];
        foreach (self::bidang($jenis) as $kunci => $def) {
            $nilai = $masukan[$kunci] ?? $def['bawaan'];
            $hasil[$kunci] = match ($def['jenis']) {
                'boolean' => (bool) $nilai,
                'angka' => (int) $nilai,
                'daftar' => array_values(array_filter(array_map('strval', (array) $nilai), fn ($v) => trim($v) !== '')),
                default => $nilai,
            };
        }

        return $hasil;
    }
}
