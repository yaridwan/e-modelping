<?php

namespace Database\Seeders;

use App\Models\PertanyaanUmum;
use Illuminate\Database\Seeder;

class PertanyaanUmumSeeder extends Seeder
{
    public function run(): void
    {
        if (PertanyaanUmum::query()->exists()) {
            return;
        }
        $faq = [
            ['Model PING', 'Apa kepanjangan PING?', 'PING merupakan singkatan dari tiga fase pembiasaan: Pre-Class Greeting (sapaan sebelum pembelajaran), Inter-Class Transition Greeting (sapaan saat perpindahan kegiatan), dan End-Day Closing Greeting (sapaan penutup menjelang pulang).'],
            ['Model PING', 'Untuk usia berapa model ini dirancang?', 'Model Pembiasaan PING dirancang untuk penguatan karakter anak usia 5–6 tahun di satuan PAUD, dengan panduan utama bagi guru.'],
            ['Model PING', 'Karakter apa saja yang dibiasakan?', 'Empat aspek karakter: disiplin, tanggung jawab, kontrol diri, dan sopan santun. Setiap aspek memiliki empat indikator perilaku sehingga total terdapat 16 indikator.'],
            ['Model PING', 'Apakah PING menambah kegiatan baru di kelas?', 'Tidak. PING diintegrasikan ke rutinitas yang sudah ada—kedatangan, perpindahan kegiatan, dan kepulangan—sehingga tidak menambah beban kegiatan terpisah dan tidak menggantikan kurikulum.'],
            ['Instrumen', 'Bagaimana jika suatu perilaku belum sempat diamati?', 'Kosongkan skor dan tuliskan “belum ada kesempatan pengamatan”. Perilaku yang belum memperoleh kesempatan diamati tidak otomatis diberi skor BB.'],
            ['Penggunaan website', 'Apakah saya perlu mendaftar untuk membaca?', 'Tidak. Seluruh materi yang diterbitkan dapat dibaca tanpa akun. Login hanya digunakan pengelola website.'],
            ['Penggunaan website', 'Apakah website menyimpan nilai anak?', 'Tidak. Website ini tidak mengumpulkan data anak dan tidak menyimpan hasil penilaian. Lembar observasi dan rubrik disajikan sebagai materi untuk digunakan guru secara mandiri.'],
            ['Penggunaan website', 'Mengapa tombol unduh PDF tidak muncul?', 'Dokumen PDF mungkin belum diterbitkan atau pengelola menonaktifkan unduhan. Materi HTML pada halaman bab tetap dapat dibaca lengkap.'],
        ];
        foreach ($faq as $i => [$kategori, $tanya, $jawab]) {
            PertanyaanUmum::query()->create(['kategori' => $kategori, 'pertanyaan' => $tanya, 'jawaban' => $jawab, 'urutan' => $i + 1, 'aktif' => true]);
        }
    }
}
