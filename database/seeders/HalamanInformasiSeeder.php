<?php

namespace Database\Seeders;

use App\Enums\StatusKonten;
use App\Layanan\LayananSanitasiHtml;
use App\Models\HalamanInformasi;
use Illuminate\Database\Seeder;

class HalamanInformasiSeeder extends Seeder
{
    public function run(): void
    {
        $sanitasi = app(LayananSanitasiHtml::class);
        foreach ($this->halaman() as $h) {
            if (HalamanInformasi::query()->where('kode_halaman', $h['kode_halaman'])->exists()) {
                continue;
            }
            $m = new HalamanInformasi;
            $m->forceFill([...$h, 'isi' => $sanitasi->bersihkan($h['isi']), 'status' => StatusKonten::Terbit, 'diterbitkan_pada' => now()])->save();
        }
    }

    private function halaman(): array
    {
        return [
            [
                'kode_halaman' => 'tentang', 'slug' => 'tentang', 'halaman_inti' => true, 'tampilkan_di_navigasi' => true,
                'judul' => 'Tentang Model Pembiasaan PING',
                'ringkasan' => 'PING adalah model pembiasaan karakter anak usia 5–6 tahun yang memanfaatkan tiga momentum harian: kedatangan, perpindahan kegiatan, dan menjelang pulang.',
                'judul_seo' => 'Tentang Model Pembiasaan PING dan Penyusun',
                'deskripsi_seo' => 'Mengenal Model Pembiasaan PING (Pre-Class, Inter-Class Transition, dan End-Day Closing Greeting), tujuan, sasaran pengguna, serta penyusun e-modul.',
                'isi' => <<<'HTML'
<p>E-Model Pembiasaan PING (<em>Pre-Class Greeting</em>, <em>Inter-Class Transition Greeting</em>, dan <em>End-Day Closing Greeting</em>) dikembangkan untuk mendukung penguatan pendidikan karakter pada anak usia dini melalui pembiasaan yang sederhana, sistematis, konsisten, dan terintegrasi dalam kegiatan pembelajaran sehari-hari.</p>
<p>Model ini memanfaatkan tiga momentum penting: momen kedatangan, perpindahan kegiatan, dan persiapan pulang. Ketiganya dirancang menjadi kesempatan bermakna untuk menanamkan disiplin, tanggung jawab, kontrol diri, dan sopan santun anak usia 5–6 tahun.</p>
<h2>Panduan praktis bagi guru PAUD</h2>
<p>E-model tidak hanya berisi uraian konseptual, tetapi juga landasan teori, konsep model, prinsip pelaksanaan, sintaks setiap fase, peran guru, indikator karakter, instrumen observasi, rubrik penilaian, contoh implementasi, serta evaluasi dan refleksi. Guru dapat mengintegrasikan pembiasaan karakter ke dalam rutinitas tanpa menambah beban kegiatan terpisah.</p>
<h2>Kolaborasi</h2>
<p>Keberhasilan penerapan Model PING memerlukan keterlibatan guru, kepala satuan PAUD, orang tua, pengawas atau fasilitator, serta seluruh warga sekolah agar budaya karakter tumbuh secara berkelanjutan.</p>
<h2>Tentang website ini</h2>
<p>Website ini adalah e-modul publik personal. Seluruh materi yang diterbitkan dapat dibaca tanpa akun. Website tidak mengumpulkan data anak dan tidak menyediakan penilaian karakter anak secara daring; instrumen yang ditampilkan adalah materi panduan untuk digunakan guru di kelas.</p>
HTML,
            ],
            [
                'kode_halaman' => 'panduan-penggunaan', 'slug' => 'panduan-penggunaan', 'halaman_inti' => true, 'tampilkan_di_navigasi' => true,
                'judul' => 'Panduan Penggunaan E-Modul',
                'ringkasan' => 'Cara memilih bab, membaca materi, memakai fitur baca, serta membuka atau mengunduh dokumen PDF.',
                'isi' => <<<'HTML'
<h2>1. Pilih bab</h2>
<p>Buka menu <strong>Modul</strong> untuk melihat sembilan bab. Setiap kartu menampilkan nomor bab, judul, ringkasan, perkiraan waktu baca, dan ketersediaan dokumen PDF.</p>
<h2>2. Baca penjelasan</h2>
<p>Materi utama setiap bab disajikan sebagai teks HTML yang dapat dibaca di ponsel maupun komputer. Gunakan <strong>daftar isi</strong> untuk melompat ke subbab. Di ponsel, daftar isi dibuka melalui tombol “Daftar isi”.</p>
<h2>3. Sesuaikan tampilan baca</h2>
<p>Atur ukuran huruf, lebar baca, dan tema terang/gelap. Preferensi serta penanda bacaan disimpan di perangkat Anda sendiri (penyimpanan lokal browser), bukan di server, dan dapat dihapus kapan saja.</p>
<h2>4. Lihat contoh dan fase</h2>
<p>Halaman <strong>Fase PING</strong> menjelaskan langkah, durasi, dan contoh dialog. Halaman <strong>Aspek Karakter</strong> memuat 16 indikator beserta rubrik sebagai referensi pengamatan.</p>
<h2>5. Buka atau unduh dokumen</h2>
<p>Bila dokumen PDF bab sudah terbit, Anda dapat membacanya di penampil, membukanya di tab baru, atau mengunduhnya bila diizinkan. Bila PDF belum tersedia, materi HTML tetap dapat dibaca lengkap.</p>
<h2>6. Cari materi</h2>
<p>Gunakan kotak pencarian untuk menemukan kata kunci pada judul, ringkasan, dan isi subbab yang sudah terbit. Pencarian tidak mencakup isi berkas PDF.</p>
HTML,
            ],
            [
                'kode_halaman' => 'kontak', 'slug' => 'kontak', 'halaman_inti' => true, 'tampilkan_di_navigasi' => true,
                'judul' => 'Kontak',
                'ringkasan' => 'Kirim pertanyaan, masukan, atau koreksi materi kepada pengelola e-modul.',
                'isi' => '<p>Saran dan masukan yang konstruktif sangat diharapkan untuk penyempurnaan e-model ini. Pesan yang Anda kirim hanya dapat dibaca oleh pengelola website dan tidak ditampilkan kepada publik.</p>',
            ],
            [
                'kode_halaman' => 'kebijakan-privasi', 'slug' => 'kebijakan-privasi', 'halaman_inti' => true, 'tampilkan_di_navigasi' => false,
                'judul' => 'Kebijakan Privasi',
                'ringkasan' => 'Data apa yang dicatat website ini, untuk apa, berapa lama, dan pilihan yang Anda miliki.',
                'isi' => <<<'HTML'
<h2>Membaca tanpa akun</h2>
<p>Seluruh materi terbit dapat dibaca tanpa registrasi atau login. Hanya pengelola (admin) yang memiliki akun.</p>
<h2>Penyimpanan di perangkat Anda</h2>
<p>Preferensi tema disimpan dalam cookie <code>appearance</code>. Ukuran huruf, lebar baca, penanda bacaan, dan posisi terakhir disimpan di <em>localStorage</em> browser Anda. Data ini tidak dikirim ke server dan dapat dihapus melalui tombol “Hapus riwayat baca” atau pengaturan browser.</p>
<h2>Statistik kunjungan</h2>
<p>Untuk mengetahui bab yang paling dibutuhkan, website mencatat statistik sederhana: jalur halaman, tanggal, jenis perangkat (desktop/tablet/ponsel), domain perujuk tanpa alamat lengkap, serta sidik pengunjung berupa kode pseudonim. Sidik dibuat dari alamat IP yang dipotong dan user agent, lalu di-hash dengan kunci rahasia; alamat IP mentah tidak disimpan. Kunjungan dihitung sekali per halaman per hari. Pengunjung unik adalah perkiraan, bukan identitas orang.</p>
<p>Website tidak memasang Google Analytics atau pelacak pihak ketiga. Data statistik dihapus otomatis setelah masa retensi yang ditetapkan pengelola.</p>
<h2>Dokumen PDF</h2>
<p>Saat dokumen dibaca atau diunduh, server mencatat satu peristiwa baca/unduh per dokumen per hari dengan sidik pseudonim yang sama. Angka unduhan berarti respons unduhan dilayani server, bukan bukti berkas tersimpan di perangkat.</p>
<h2>Formulir kontak</h2>
<p>Nama, surel, institusi (opsional), subjek, dan isi pesan disimpan agar pengelola dapat membaca dan menindaklanjuti pesan. Data ini tidak dipublikasikan dan tidak dibagikan. Pesan yang sudah diarsipkan dihapus otomatis setelah masa retensi.</p>
<h2>Video pihak ketiga</h2>
<p>Video pendukung (YouTube mode privasi atau Vimeo) baru dimuat setelah Anda menekan tombol putar.</p>
<h2>Tidak ada data anak</h2>
<p>Website tidak mengumpulkan data anak dan tidak menyimpan hasil penilaian karakter anak. Instrumen yang ditampilkan adalah materi panduan untuk digunakan guru secara mandiri.</p>
HTML,
            ],
            [
                'kode_halaman' => 'pertanyaan-umum', 'slug' => 'pertanyaan-umum', 'halaman_inti' => true, 'tampilkan_di_navigasi' => false,
                'judul' => 'Pertanyaan Umum',
                'ringkasan' => 'Jawaban singkat atas pertanyaan yang sering muncul tentang Model PING dan penggunaan e-modul.',
                'isi' => null,
            ],
            [
                'kode_halaman' => 'galeri', 'slug' => 'galeri', 'halaman_inti' => true, 'tampilkan_di_navigasi' => false,
                'judul' => 'Galeri Media Pendukung',
                'ringkasan' => 'Media pendukung penerapan Model PING yang telah diterbitkan pengelola.',
                'isi' => '<p>Dokumentasi yang memuat anak hanya diterbitkan apabila pengelola memiliki izin publikasi.</p>',
            ],
            [
                'kode_halaman' => 'pesan-untuk-guru', 'slug' => 'pesan-untuk-guru-paud', 'halaman_inti' => false, 'tampilkan_di_navigasi' => false,
                'judul' => 'Pesan untuk Guru PAUD',
                'ringkasan' => 'Pesan penutup e-modul tentang arti konsistensi dalam pembiasaan karakter sehari-hari.',
                'isi' => <<<'HTML'
<p>Bapak dan Ibu Guru PAUD yang berdedikasi,</p>
<p>Di depan pintu kelas, seorang anak mungkin masih menggenggam tangan orang tuanya. Guru menyebut namanya, mengucapkan salam, lalu memberi waktu baginya untuk menjawab. Hari ini, anak itu mungkin hanya tersenyum. Beberapa hari kemudian, bisa jadi ia yang lebih dahulu menyapa. Di antara senyum pertama dan salam yang akhirnya terucap, ada guru yang setiap pagi menyambutnya dengan kehangatan yang sama. Di situlah arti konsistensi bagi seorang anak.</p>
<p>Melalui Model Pembiasaan PING, kesempatan itu hadir sejak anak datang hingga ia pulang: sapaan “Terima kasih sudah datang tepat waktu” di pagi hari, isyarat yang sudah dikenal saat bermain berakhir, dan pertanyaan sederhana seperti “Siapa yang kamu bantu hari ini?” menjelang pulang.</p>
<p>Ada hari ketika anak bersedia menunggu giliran, lalu keesokan harinya ia kembali menyerobot antrean. Pada saat seperti itu, konsistensi tampak dalam kesediaan guru untuk membimbingnya sekali lagi—dan menyebutkan usahanya ketika ia berhasil.</p>
<blockquote><p>“Apa yang hari ini anak lakukan bersama guru, perlahan dapat menjadi kebiasaan yang ia bawa pulang dan lakukan atas kemauannya sendiri.”</p></blockquote>
<aside class="rujukan-sumber"><p>Disarikan dari bagian “Pesan untuk Guru PAUD” pada dokumen modul (hlm. 110–111).</p></aside>
HTML,
            ],
        ];
    }
}
