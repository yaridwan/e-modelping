<?php

namespace Database\Seeders;

use App\Models\PengaturanWebsite;
use Illuminate\Database\Seeder;

/** Singleton identitas website; dibuat sekali, tidak menimpa suntingan pemilik. */
class PengaturanWebsiteSeeder extends Seeder
{
    public function run(): void
    {
        if (PengaturanWebsite::query()->where('kunci_tunggal', 'utama')->exists()) {
            return;
        }

        $p = new PengaturanWebsite;
        $p->forceFill([
            'kunci_tunggal' => 'utama',
            'nama_aplikasi' => 'E-Model PING',
            'nama_singkat' => 'PING',
            'tagline' => 'Model Pembiasaan Karakter Anak Usia Dini',
            'deskripsi_singkat' => 'E-modul publik Model Pembiasaan PING (Pre-Class Greeting, Inter-Class Transition Greeting, dan End-Day Closing Greeting) untuk membantu guru PAUD membiasakan disiplin, tanggung jawab, kontrol diri, dan sopan santun anak usia 5–6 tahun.',
            // Atribusi materi dari PDF sumber. Kontak, alamat, dan akun sosial penyusun tidak ditebak.
            'nama_pemilik' => 'Tita Sapriyanti',
            'nama_institusi' => null,
            'surel_kontak' => null,
            'nomor_whatsapp' => null,
            'alamat_publik' => null,
            'kota_kabupaten' => null,
            'provinsi' => null,
            'bahasa' => 'id',
            'zona_waktu' => 'Asia/Jakarta',
            'warna_utama' => '#BE185D',
            'warna_pendamping' => '#EC4899',
            'tema_bawaan' => 'sistem',
            'teks_footer' => 'E-modul pembiasaan karakter untuk guru PAUD. Materi disusun berdasarkan E-Model Pembiasaan PING.',
            'tahun_hak_cipta_awal' => 2026,
            'judul_seo_bawaan' => 'E-Model PING — Model Pembiasaan Karakter Anak Usia Dini',
            'templat_judul_seo' => '%judul% | %nama%',
            'deskripsi_seo_bawaan' => 'Panduan guru PAUD untuk membiasakan disiplin, tanggung jawab, kontrol diri, dan sopan santun anak usia 5–6 tahun melalui tiga fase sapaan: Pre-Class, Inter-Class Transition, dan End-Day Closing Greeting.',
            'jenis_identitas_penerbit' => 'orang',
            'izinkan_unduh' => true,
            'batas_unggah_mb' => min(25, (int) config('ping.batas_unggah_maksimum_mb', 25)),
            'panduan_penamaan_versi' => "Gunakan format MAYOR.MINOR, mis. 1.0 untuk terbitan awal, 1.1 untuk perbaikan kecil, 2.0 untuk revisi isi besar.\nTulis catatan perubahan singkat pada setiap versi.",
            'statistik_aktif' => true,
            'kecualikan_admin' => true,
            'kecualikan_bot' => true,
            'retensi_statistik_hari' => 365,
            'retensi_pesan_hari' => 730,
        ])->save();
    }
}
