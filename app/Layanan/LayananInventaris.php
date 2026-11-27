<?php

namespace App\Layanan;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Matriks inventaris seluruh tabel domain untuk dashboard: jumlah baris dan rincian status.
 * Token pemulihan hanya direpresentasikan sebagai jumlah aktif/kedaluwarsa/terpakai.
 */
class LayananInventaris
{
    /** @return list<array{tabel: string, label: string, modul: string, total: int, rincian: array<string, int>}> */
    public function matriks(): array
    {
        $definisi = [
            ['pengguna', 'Pengguna (profil admin)', 'Akun', 'status'],
            ['pengaturan_website', 'Pengaturan website', 'Identitas', null],
            ['bagian_beranda', 'Bagian beranda', 'Beranda', 'aktif'],
            ['menu_navigasi', 'Menu navigasi', 'Navigasi', 'lokasi'],
            ['tautan_sosial', 'Tautan sosial', 'Navigasi', 'aktif'],
            ['halaman_informasi', 'Halaman informasi', 'Konten', 'status'],
            ['bab_modul', 'Bab modul', 'Konten', 'status'],
            ['subbab_modul', 'Subbab modul', 'Konten', 'status'],
            ['fase_ping', 'Fase PING', 'Materi referensi', 'status'],
            ['langkah_fase', 'Langkah fase', 'Materi referensi', null],
            ['aspek_karakter', 'Aspek karakter', 'Materi referensi', 'aktif'],
            ['indikator_karakter', 'Indikator karakter', 'Materi referensi', 'aktif'],
            ['referensi_pustaka', 'Referensi pustaka', 'Materi referensi', 'status_verifikasi'],
            ['pertanyaan_umum', 'Pertanyaan umum (FAQ)', 'Konten', 'aktif'],
            ['galeri_kegiatan', 'Galeri kegiatan', 'Media', 'status'],
            ['berkas_media', 'Berkas media', 'Media', 'jenis_media'],
            ['versi_dokumen', 'Versi dokumen', 'Dokumen', 'status'],
            ['pesan_kontak', 'Pesan kontak', 'Pesan', 'status'],
            ['kunjungan_halaman', 'Kunjungan halaman', 'Statistik', 'data_contoh'],
            ['aktivitas_dokumen', 'Aktivitas dokumen', 'Statistik', 'jenis_aktivitas'],
            ['log_aktivitas', 'Log aktivitas', 'Audit', 'data_contoh'],
            ['pengalihan_url', 'Pengalihan URL', 'SEO', 'aktif'],
        ];

        $hasil = [];
        foreach ($definisi as [$tabel, $label, $modul, $kolom]) {
            $total = (int) DB::table($tabel)->count();
            $rincian = [];
            if ($kolom) {
                $rincian = DB::table($tabel)->selectRaw("{$kolom} as kunci, COUNT(*) as jumlah")->groupBy($kolom)->pluck('jumlah', 'kunci')
                    ->mapWithKeys(fn ($j, $k) => [$this->labelNilai($kolom, $k) => (int) $j])->all();
            }
            $hasil[] = compact('tabel', 'label', 'modul', 'total', 'rincian');
        }

        $sekarang = now();
        $hasil[] = [
            'tabel' => 'token_atur_ulang_sandi',
            'label' => 'Token atur ulang sandi',
            'modul' => 'Keamanan',
            'total' => (int) DB::table('token_atur_ulang_sandi')->count(),
            'rincian' => [
                'Aktif' => (int) DB::table('token_atur_ulang_sandi')->whereNull('digunakan_pada')->where('kedaluwarsa_pada', '>', $sekarang)->count(),
                'Kedaluwarsa' => (int) DB::table('token_atur_ulang_sandi')->whereNull('digunakan_pada')->where('kedaluwarsa_pada', '<=', $sekarang)->count(),
                'Terpakai' => (int) DB::table('token_atur_ulang_sandi')->whereNotNull('digunakan_pada')->count(),
            ],
        ];

        return $hasil;
    }

    /** Panel kesehatan teknis (metadata, bukan data domain). */
    public function kesehatan(): array
    {
        $tabelMigrasi = (string) config('database.migrations.table', 'riwayat_migrasi');
        $versiDb = (string) (DB::selectOne('select version() as v')->v ?? '-');

        return [
            'riwayat_migrasi' => Schema::hasTable($tabelMigrasi) ? (int) DB::table($tabelMigrasi)->count() : 0,
            'kelompok_migrasi_terakhir' => Schema::hasTable($tabelMigrasi) ? (int) DB::table($tabelMigrasi)->max('kelompok') : 0,
            'versi_basis_data' => $versiDb,
            'versi_php' => PHP_VERSION,
            'versi_laravel' => app()->version(),
            'driver_sesi' => (string) config('session.driver'),
            'driver_cache' => (string) config('cache.default'),
            'antrean' => (string) config('queue.default'),
            'ssr_aktif' => (bool) config('inertia.ssr.enabled'),
            'lingkungan' => app()->environment(),
        ];
    }

    private function labelNilai(string $kolom, mixed $nilai): string
    {
        if (in_array($kolom, ['aktif', 'data_contoh'], true)) {
            return $kolom === 'aktif' ? ((int) $nilai ? 'Aktif' : 'Nonaktif') : ((int) $nilai ? 'Demo' : 'Nyata');
        }

        return ucfirst(str_replace('_', ' ', (string) $nilai));
    }
}
