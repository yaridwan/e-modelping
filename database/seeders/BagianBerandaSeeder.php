<?php

namespace Database\Seeders;

use App\Models\BagianBeranda;
use App\Support\SkemaBagianBeranda;
use Illuminate\Database\Seeder;

class BagianBerandaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->bagian() as $i => $b) {
            if (BagianBeranda::query()->where('kode_bagian', $b['kode_bagian'])->exists()) {
                continue;
            }
            $m = new BagianBeranda;
            $m->forceFill([...$b, 'urutan' => $i + 1, 'aktif' => true, 'pengaturan_tampilan' => SkemaBagianBeranda::saring($b['jenis_bagian'], $b['pengaturan_tampilan'] ?? [])])->save();
        }
    }

    private function bagian(): array
    {
        return [
            ['kode_bagian' => 'hero', 'jenis_bagian' => 'hero', 'judul' => 'Bangun Kebiasaan Baik Anak melalui Model PING',
                'subjudul' => 'Panduan guru PAUD',
                'ringkasan' => 'Tiga momen sapaan harian—kedatangan, perpindahan kegiatan, dan menjelang pulang—menjadi kesempatan membiasakan disiplin, tanggung jawab, kontrol diri, dan sopan santun anak usia 5–6 tahun.',
                'teks_tombol_utama' => 'Jelajahi 9 Bab', 'tautan_tombol_utama' => '/modul',
                'teks_tombol_pendamping' => 'Kenali Model PING', 'tautan_tombol_pendamping' => '/tentang'],
            ['kode_bagian' => 'pengantar', 'jenis_bagian' => 'pengantar', 'judul' => 'Apa itu PING?',
                'subjudul' => 'Pre-Class Greeting · Inter-Class Transition Greeting · End-Day Closing Greeting',
                'ringkasan' => 'Model Pembiasaan PING mengintegrasikan budaya salam, interaksi positif, keteladanan guru, penguatan perilaku, dan refleksi harian ke dalam tiga tahapan kegiatan sekolah. Pembentukan karakter tidak berlangsung insidental, tetapi menjadi budaya belajar yang konsisten.',
                'pengaturan_tampilan' => ['manfaat' => [
                    'Langkah pelaksanaan setiap fase yang jelas dan berurutan.',
                    'Contoh dialog singkat yang sesuai bahasa anak usia 5–6 tahun.',
                    'Indikator, rubrik, dan instrumen observasi sebagai panduan pengamatan.',
                    'Terintegrasi dalam rutinitas tanpa menambah kegiatan terpisah.',
                ]]],
            ['kode_bagian' => 'fase', 'jenis_bagian' => 'fase', 'judul' => 'Tiga fase pembiasaan',
                'ringkasan' => 'Setiap fase memiliki fungsi, durasi panduan, dan langkah pokok yang saling melengkapi dari pagi hingga pulang.'],
            ['kode_bagian' => 'aspek', 'jenis_bagian' => 'aspek', 'judul' => 'Empat aspek karakter',
                'ringkasan' => 'Setiap aspek dijabarkan menjadi empat indikator perilaku yang dapat diamati, sehingga total terdapat 16 indikator.',
                'teks_tombol_utama' => 'Lihat 16 indikator', 'tautan_tombol_utama' => '/karakter'],
            ['kode_bagian' => 'bab', 'jenis_bagian' => 'bab', 'judul' => 'Sembilan bab e-modul',
                'ringkasan' => 'Mulai dari pendahuluan hingga evaluasi implementasi. Pilih bab sesuai kebutuhan Anda.',
                'teks_tombol_utama' => 'Lihat semua bab', 'tautan_tombol_utama' => '/modul'],
            ['kode_bagian' => 'panduan', 'jenis_bagian' => 'panduan', 'judul' => 'Cara menggunakan e-modul',
                'teks_tombol_utama' => 'Baca panduan lengkap', 'tautan_tombol_utama' => '/panduan-penggunaan',
                'pengaturan_tampilan' => ['langkah' => [
                    'Pilih bab | Telusuri sembilan bab dan pilih yang Anda butuhkan.',
                    'Baca penjelasan | Materi HTML dapat dibaca nyaman di ponsel maupun komputer.',
                    'Lihat contoh | Pelajari langkah fase, contoh dialog, dan indikator karakter.',
                    'Buka atau unduh | Baca dokumen PDF bila tersedia, atau unduh bila diizinkan.',
                ]]],
            ['kode_bagian' => 'profil', 'jenis_bagian' => 'profil', 'judul' => 'Penyusun',
                'ringkasan' => 'E-Model Pembiasaan PING disusun oleh Tita Sapriyanti sebagai panduan praktis bagi guru PAUD.'],
            ['kode_bagian' => 'galeri', 'jenis_bagian' => 'galeri', 'judul' => 'Galeri media pendukung',
                'ringkasan' => 'Media pendukung yang telah diterbitkan.', 'teks_tombol_utama' => 'Buka galeri', 'tautan_tombol_utama' => '/galeri'],
            ['kode_bagian' => 'faq', 'jenis_bagian' => 'faq', 'judul' => 'Pertanyaan umum',
                'teks_tombol_utama' => 'Semua pertanyaan', 'tautan_tombol_utama' => '/pertanyaan-umum'],
            ['kode_bagian' => 'cta', 'jenis_bagian' => 'cta', 'judul' => 'Mulai membiasakan karakter baik hari ini',
                'ringkasan' => 'Baca bab pertama atau langsung pelajari sintaks tiga fase PING.',
                'teks_tombol_utama' => 'Mulai membaca', 'tautan_tombol_utama' => '/modul/pendahuluan',
                'teks_tombol_pendamping' => 'Sintaks model', 'tautan_tombol_pendamping' => '/modul/sintaks-model'],
        ];
    }
}
