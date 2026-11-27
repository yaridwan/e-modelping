"""Kurasi data seeder dari hasil ekstraksi PDF sumber E-Model PING.

Menggabungkan keluaran `ekstrak_materi.py` dengan data terstruktur yang
ditranskripsikan dari tabel PDF (fase, langkah, aspek, indikator, rubrik,
referensi). Hasil akhirnya adalah `database/seeders/data/materi-sumber.json`
yang dibaca oleh seeder Laravel.

Pemakaian:
    python -I -X utf8 kurasi_materi.py materi.json ../../database/seeders/data/materi-sumber.json
"""

from __future__ import annotations

import html
import json
import re
import sys

E = html.escape

# ---------------------------------------------------------------------------
# Data terstruktur hasil transkripsi tabel sumber (nomor halaman = halaman cetak)
# ---------------------------------------------------------------------------

FASE = [
    {
        "kode_fase": "PCG", "nama": "Pre-Class Greeting", "slug": "pre-class-greeting",
        "judul_indonesia": "Sapaan Awal Sebelum Pembelajaran",
        "deskripsi": "Fase awal Model Pembiasaan PING yang dilaksanakan ketika anak datang ke sekolah hingga sebelum kegiatan pembelajaran dimulai. Penyambutan yang hangat, komunikasi yang menyenangkan, dan pembiasaan perilaku positif membangun rasa aman serta kesiapan belajar anak.",
        "tujuan": [
            "Membantu anak beradaptasi dari lingkungan rumah menuju lingkungan sekolah.",
            "Membangun hubungan emosional yang positif antara guru dan anak.",
            "Menumbuhkan rasa aman, nyaman, dan percaya diri sebelum pembelajaran dimulai.",
            "Membiasakan anak menerapkan budaya salam, senyum, sapa, serta perilaku santun.",
            "Menanamkan disiplin melalui kebiasaan datang tepat waktu dan mengikuti rutinitas pagi.",
            "Mempersiapkan kondisi fisik dan emosional anak agar siap mengikuti kegiatan belajar.",
            "Menciptakan suasana kelas yang positif sejak awal pembelajaran.",
        ],
        "waktu_pelaksanaan": "Setiap hari saat anak datang ke sekolah, memasuki halaman sekolah, menuju ruang kelas, dan sebelum kegiatan pembukaan dimulai.",
        "durasi_minimum_menit": 10, "durasi_maksimum_menit": 15,
        "frekuensi_panduan": "Satu kali setiap hari pada saat kedatangan anak.",
        "catatan_guru": "Hindari teguran bernada tinggi ketika anak baru datang; gunakan bahasa yang positif dan sederhana; perhatikan kondisi emosional setiap anak; berikan perhatian yang sama kepada seluruh anak; jadilah teladan budaya 5S. Anak yang datang kurang bersemangat didekati secara individual melalui komunikasi yang hangat.",
        "indikator_keberhasilan": [
            "≥80% anak hadir sesuai jadwal.",
            "≥80% anak menyapa guru.",
            "≥80% anak merespons sapaan guru secara positif.",
            "≥80% anak menyimpan barang secara mandiri.",
            "≥80% anak mengikuti rutinitas pagi tanpa diingatkan berulang.",
            "≥80% anak masuk kelas dan mengikuti transisi dengan tertib.",
        ],
        "aktivitas_guru": ["Menyambut seluruh anak dengan senyum dan salam.", "Memanggil nama anak.", "Membangun komunikasi positif.", "Mengamati kondisi emosional anak.", "Mengarahkan anak mengikuti rutinitas pagi.", "Memberikan contoh perilaku santun."],
        "aktivitas_anak": ["Datang tepat waktu.", "Mengucapkan salam.", "Menjawab sapaan guru.", "Berjabat tangan dengan guru.", "Menyimpan barang pada tempatnya.", "Mengikuti aturan pagi.", "Berinteraksi dengan teman secara santun.", "Siap mengikuti pembelajaran."],
        "penguatan": {"Disiplin": "Datang tepat waktu, mengikuti rutinitas pagi.", "Tanggung jawab": "Merapikan tas dan perlengkapan belajar.", "Kontrol diri": "Menunggu giliran masuk kelas, mengikuti arahan guru.", "Sopan santun": "Salam, senyum, sapa, berjabat tangan, berbicara sopan."},
        "halaman": "52–58",
        "langkah": [
            ("Penyambutan kedatangan anak", "Guru berdiri di gerbang atau depan kelas, memberikan senyum, salam, dan sapaan, melakukan kontak mata, serta memanggil nama anak dengan ramah.",
             "Menyambut anak dengan senyum, salam, sapaan, dan memanggil namanya.", "Disambut dan mulai merespons sapaan guru.",
             [("Guru", "Selamat pagi, Aisyah."), ("Guru", "Apa kabar hari ini?")]),
            ("Respons salam anak", "Anak menjawab salam guru, mengucapkan salam, berjabat tangan, dan memberi senyum. Guru memberikan respons positif.",
             "Memberi respons positif atas salam anak.", "Menjawab salam, mengucapkan salam, berjabat tangan, dan tersenyum.", []),
            ("Pengamatan kesiapan anak", "Guru memperhatikan kondisi masing-masing anak: wajah, semangat belajar, kebersihan diri, perlengkapan belajar, dan kondisi emosional. Anak yang tampak murung didekati secara personal.",
             "Mengamati kesiapan fisik dan emosional anak serta melakukan pendekatan personal bila perlu.", "Menunjukkan kesiapan dan menyampaikan kondisinya.", []),
            ("Rutinitas disiplin", "Guru mengingatkan anak untuk menyimpan tas, melepas alas kaki, merapikan perlengkapan, mengisi papan kehadiran, dan berbaris. Semua dilakukan dengan arahan yang positif.",
             "Mengarahkan rutinitas pagi dengan bahasa positif.", "Menyimpan tas, melepas alas kaki, merapikan perlengkapan, mengisi papan kehadiran, dan berbaris.", []),
            ("Penguatan karakter", "Guru memberikan pujian pada setiap perilaku positif yang dimunculkan anak secara spesifik.",
             "Memberikan pujian spesifik atas perilaku positif.", "Menerima apresiasi dan mengulang perilaku positif.",
             [("Guru", "Terima kasih sudah datang tepat waktu."), ("Guru", "Hari ini Rafi sudah menyapa teman dengan ramah.")]),
            ("Transisi menuju pembelajaran", "Guru mengajak anak berbaris, bernyanyi, berdoa, dan masuk kelas dengan tertib, lalu melanjutkan ke kegiatan pembukaan.",
             "Memimpin barisan, nyanyian, dan doa sebelum masuk kelas.", "Berbaris, bernyanyi, berdoa, dan masuk kelas dengan tertib.", []),
        ],
        "dialog_fase": [("Guru", "Selamat pagi, Alya."), ("Anak", "Selamat pagi, Bu Guru."), ("Guru", "Bagaimana kabarnya hari ini?"), ("Anak", "Baik."), ("Guru", "Alhamdulillah. Yuk kita simpan tasnya dulu, kemudian beri salam kepada teman.")],
    },
    {
        "kode_fase": "ICTG", "nama": "Inter-Class Transition Greeting", "slug": "inter-class-transition-greeting",
        "judul_indonesia": "Sapaan Transisi Antarkegiatan",
        "deskripsi": "Fase kedua Model Pembiasaan PING yang dilaksanakan setiap kali terjadi perpindahan dari satu aktivitas pembelajaran ke aktivitas berikutnya. Transisi dipandang sebagai kesempatan memperkuat karakter melalui komunikasi positif, isyarat yang konsisten, dan arahan singkat yang jelas.",
        "tujuan": [
            "Membantu anak melakukan perpindahan kegiatan secara tertib dan menyenangkan.",
            "Mengembangkan kemampuan anak mengikuti aturan dan rutinitas kelas.",
            "Menumbuhkan tanggung jawab terhadap kegiatan yang telah diselesaikan.",
            "Melatih kemampuan mengendalikan diri ketika terjadi perubahan aktivitas.",
            "Membiasakan komunikasi yang santun antara guru dan anak maupun antarteman.",
            "Mengurangi perilaku yang mengganggu selama transisi pembelajaran.",
            "Menciptakan suasana belajar yang tetap kondusif pada setiap pergantian kegiatan.",
        ],
        "waktu_pelaksanaan": "Setiap kali terjadi perpindahan aktivitas, misalnya dari pembukaan ke kegiatan inti, antarsentra, dari bermain ke belajar, ke makan bersama, ke bermain luar ruang, hingga menuju kegiatan penutup.",
        "durasi_minimum_menit": 2, "durasi_maksimum_menit": 5,
        "frekuensi_panduan": "3–6 kali dalam satu hari pembelajaran, sesuai struktur kegiatan lembaga.",
        "catatan_guru": "Berikan tanda transisi secara konsisten; hindari instruksi panjang atau beberapa perintah sekaligus; gunakan nada tenang, jelas, dan positif; beri waktu cukup untuk menyelesaikan aktivitas; apresiasi perilaku positif sekecil apa pun; perhatikan anak yang masih memerlukan bantuan lebih banyak.",
        "indikator_keberhasilan": [
            "≥80% anak menghentikan kegiatan saat diberi isyarat.",
            "≥80% anak merapikan alat bermain.",
            "≥80% anak berpindah dengan tertib.",
            "≥80% anak menunggu giliran.",
            "≥80% anak berbicara sopan.",
            "≥80% anak siap mengikuti kegiatan berikutnya.",
        ],
        "aktivitas_guru": ["Memberikan tanda perpindahan kegiatan.", "Menyampaikan instruksi secara jelas dan singkat.", "Memberikan contoh perilaku transisi yang baik.", "Mengajak anak merapikan alat bermain.", "Memberikan apresiasi terhadap perilaku positif.", "Mengarahkan perpindahan anak secara tertib.", "Mengamati perilaku setiap anak selama transisi.", "Memastikan seluruh anak siap mengikuti kegiatan berikutnya."],
        "aktivitas_anak": ["Menghentikan kegiatan ketika diberi tanda.", "Merapikan alat bermain.", "Mengikuti arahan guru.", "Berjalan dengan tertib.", "Menunggu giliran.", "Membantu teman bila diperlukan.", "Berkomunikasi dengan sopan.", "Siap mengikuti kegiatan berikutnya."],
        "penguatan": {"Disiplin": "Mengikuti isyarat transisi, berpindah sesuai aturan.", "Tanggung jawab": "Merapikan alat, menyelesaikan kegiatan sebelum berpindah.", "Kontrol diri": "Menunggu giliran, berjalan tertib, mengendalikan emosi.", "Sopan santun": "Mengucapkan salam, berbicara sopan, menghargai teman."},
        "halaman": "58–65",
        "langkah": [
            ("Isyarat transisi", "Guru memberi tanda bahwa kegiatan akan segera berakhir melalui lagu transisi, tepuk karakter, hitungan mundur, atau bunyi bel dengan bahasa yang positif.",
             "Memberikan isyarat yang konsisten dan pemberitahuan sebelum kegiatan berakhir.", "Mengenali isyarat dan bersiap menghentikan kegiatan.",
             [("Guru", "Anak-anak, lima menit lagi kita akan berpindah ke kegiatan berikutnya.")]),
            ("Penyelesaian dan perapian", "Guru mengingatkan anak menyelesaikan tugas, merapikan alat bermain, membersihkan area kegiatan, dan mengembalikan alat pada tempatnya.",
             "Mengingatkan anak menyelesaikan dan merapikan kegiatan.", "Menyelesaikan tugas dan mengembalikan alat ke tempatnya.", []),
            ("Salam transisi", "Guru mengajak anak melakukan salam transisi sebagai penanda peralihan kegiatan.",
             "Memimpin salam transisi.", "Menjawab salam transisi dengan semangat.",
             [("Guru", "Sudah siap menuju kegiatan berikutnya?"), ("Anak", "Siap..."), ("Guru", "Mari kita ucapkan, semangat belajar!")]),
            ("Penguatan karakter", "Guru memberikan apresiasi spesifik terhadap perilaku positif yang muncul selama transisi.",
             "Mengapresiasi perilaku positif secara spesifik.", "Menerima apresiasi dan mengulang perilaku positif.",
             [("Guru", "Terima kasih, kelompok B sudah merapikan balok bersama.")]),
            ("Perpindahan tertib", "Guru mengarahkan anak berjalan tertib, antre, menjaga teman, berbicara pelan, dan menuju tempat kegiatan berikutnya.",
             "Mengarahkan perpindahan secara tertib.", "Berjalan tertib, antre, dan menjaga teman.", []),
            ("Kesiapan kegiatan berikutnya", "Guru memastikan seluruh anak siap, lalu memulai aktivitas berikutnya.",
             "Memastikan kesiapan seluruh anak dan memulai kegiatan baru.", "Siap mengikuti kegiatan berikutnya.", []),
        ],
        "dialog_fase": [("Guru", "Anak-anak, sebentar lagi kita akan berpindah ke sentra seni."), ("Guru", "Sebelum berpindah, siapa yang bertanggung jawab merapikan balok?"), ("Anak", "Saya, Bu."), ("Guru", "Terima kasih. Hebat sekali karena sudah bertanggung jawab."), ("Guru", "Siapa yang hari ini sudah sabar menunggu giliran?"), ("Anak", "Saya."), ("Guru", "Luar biasa. Itu namanya anak yang bisa mengendalikan diri.")],
    },
    {
        "kode_fase": "EDCG", "nama": "End-Day Closing Greeting", "slug": "end-day-closing-greeting",
        "judul_indonesia": "Sapaan Penutup Akhir Hari",
        "deskripsi": "Fase penutup Model Pembiasaan PING yang dilaksanakan pada akhir kegiatan sebelum anak pulang. Melalui refleksi sederhana, apresiasi, penguatan nilai, dan pembiasaan berpamitan secara santun, fase ini menginternalisasi pengalaman karakter sepanjang hari.",
        "tujuan": [
            "Membantu anak merefleksikan pengalaman belajar yang telah dilakukan.",
            "Memberikan penguatan terhadap perilaku positif yang muncul selama pembelajaran.",
            "Membiasakan anak mengakhiri kegiatan dengan tertib dan santun.",
            "Menumbuhkan rasa syukur serta sikap menghargai guru dan teman.",
            "Membentuk kebiasaan berpamitan sebelum meninggalkan lingkungan sekolah.",
            "Menghubungkan pengalaman belajar di sekolah dengan kehidupan anak di rumah.",
            "Meningkatkan motivasi anak untuk kembali belajar pada hari berikutnya.",
        ],
        "waktu_pelaksanaan": "Setelah seluruh kegiatan pembelajaran dan merapikan kelas selesai, sebelum anak dijemput orang tua atau pulang.",
        "durasi_minimum_menit": 10, "durasi_maksimum_menit": 15,
        "frekuensi_panduan": "Satu kali setiap hari menjelang kepulangan anak.",
        "catatan_guru": "Gunakan kalimat yang membangun rasa percaya diri; berikan apresiasi spesifik tanpa membandingkan anak; libatkan seluruh anak dalam refleksi termasuk anak yang pendiam; hubungkan pengalaman sekolah dengan kebiasaan di rumah; pastikan setiap anak berkesempatan berpamitan.",
        "indikator_keberhasilan": [
            "≥80% anak mengikuti refleksi.",
            "≥80% anak mampu menyebutkan satu perilaku positif.",
            "≥80% anak mengucapkan salam.",
            "≥80% anak berpamitan kepada guru.",
            "≥80% anak membawa barang sendiri.",
            "≥80% anak pulang dengan tertib.",
        ],
        "aktivitas_guru": ["Mengajak anak melakukan refleksi.", "Memberikan apresiasi terhadap perilaku positif.", "Menghubungkan pengalaman dengan nilai karakter.", "Memberikan motivasi untuk hari berikutnya.", "Memimpin salam penutup.", "Mengawasi proses kepulangan anak."],
        "aktivitas_anak": ["Mengikuti refleksi bersama.", "Menceritakan pengalaman hari itu.", "Mendengarkan apresiasi guru.", "Mengucapkan terima kasih.", "Berpamitan kepada guru dan teman.", "Pulang dengan tertib."],
        "penguatan": {"Disiplin": "Mengikuti prosedur pulang dengan tertib.", "Tanggung jawab": "Membawa barang sendiri, merapikan kelas sebelum pulang.", "Kontrol diri": "Menunggu giliran dijemput dan tetap tenang.", "Sopan santun": "Mengucapkan salam, terima kasih, dan berpamitan."},
        "halaman": "65–71",
        "langkah": [
            ("Refleksi bersama", "Guru mengajak anak duduk melingkar dan mengajukan pertanyaan sederhana tentang kegiatan yang disukai, kebaikan yang dilakukan, dan teman yang dibantu.",
             "Memandu refleksi dengan pertanyaan sederhana.", "Duduk melingkar dan menceritakan pengalaman hari itu.",
             [("Guru", "Apa kegiatan yang paling kamu sukai hari ini?"), ("Guru", "Siapa yang hari ini membantu teman?")]),
            ("Apresiasi perilaku positif", "Guru memberikan penghargaan terhadap perilaku positif; apresiasi diberikan kepada usaha, bukan hanya hasil.",
             "Mengapresiasi usaha anak secara spesifik.", "Mendengarkan apresiasi guru.",
             [("Guru", "Hari ini Iqbal sangat bertanggung jawab merapikan mainan."), ("Guru", "Nadia sudah sabar menunggu giliran.")]),
            ("Penguatan nilai karakter", "Guru mengingatkan kembali empat karakter utama—disiplin, tanggung jawab, kontrol diri, dan sopan santun—dan menghubungkannya dengan pengalaman anak hari itu.",
             "Menghubungkan pengalaman hari itu dengan empat karakter.", "Mengingat kembali perilaku baik yang telah dilakukan.", []),
            ("Salam penutup", "Guru mengajak anak mengucapkan terima kasih, meminta maaf bila ada kesalahan, mengucapkan salam, dan berpamitan kepada guru dan teman.",
             "Memimpin salam penutup.", "Mengucapkan terima kasih, meminta maaf bila perlu, dan berpamitan.",
             [("Guru", "Terima kasih karena hari ini kalian sudah menjadi anak-anak yang hebat.")]),
            ("Pembiasaan pulang", "Guru mengingatkan anak membawa seluruh barang, menjaga kebersihan, berjalan tertib menuju orang tua, dan mengucapkan salam, serta memastikan seluruh anak pulang dengan aman.",
             "Mengawasi kepulangan yang aman dan tertib.", "Membawa barang sendiri, berjalan tertib, dan mengucapkan salam.",
             [("Guru", "Sampai jumpa besok. Jangan lupa membantu Ayah dan Ibu di rumah.")]),
        ],
        "dialog_fase": [("Guru", "Apa yang paling membuatmu senang hari ini?"), ("Anak", "Saya senang bermain balok bersama teman."), ("Guru", "Bagus sekali. Apakah kalian saling membantu?"), ("Anak", "Iya."), ("Guru", "Terima kasih karena hari ini kalian sudah menjadi anak-anak yang hebat."), ("Guru", "Sampai jumpa besok. Jangan lupa membantu Ayah dan Ibu di rumah.")],
    },
]

RUBRIK = {
    "disiplin": ("Belum menunjukkan perilaku disiplin meskipun telah dibimbing.", "Kadang-kadang menunjukkan perilaku disiplin dan masih sering diingatkan.", "Sering menunjukkan perilaku disiplin, sesekali masih diingatkan.", "Selalu menunjukkan perilaku disiplin tanpa diingatkan.", "6.2", 79),
    "tanggung-jawab": ("Belum menunjukkan tanggung jawab meskipun telah dibimbing.", "Masih memerlukan bimbingan dalam menyelesaikan tanggung jawab.", "Bertanggung jawab dengan sedikit arahan guru.", "Selalu bertanggung jawab tanpa bantuan guru.", "6.4", 81),
    "kontrol-diri": ("Belum mampu mengendalikan diri meskipun telah dibimbing.", "Kadang mampu mengendalikan diri tetapi masih sering dibimbing.", "Umumnya mampu mengendalikan diri dengan sedikit pengingat.", "Mampu mengendalikan diri secara mandiri pada berbagai situasi.", "6.6", 83),
    "sopan-santun": ("Belum menunjukkan perilaku sopan santun meskipun telah dibimbing.", "Kadang menunjukkan perilaku sopan santun setelah diingatkan.", "Sering menunjukkan perilaku sopan santun dengan sedikit pengingat.", "Selalu menunjukkan perilaku sopan santun secara spontan.", "6.8", 85),
}

ASPEK = [
    {"kode_aspek": "DIS", "nama": "Disiplin", "slug": "disiplin", "ikon": "list-checks", "warna": "pink",
     "deskripsi": "Kemampuan anak mengikuti kesepakatan kelas dan menjalani urutan kegiatan sehari-hari secara tertib. Rutinitas yang konsisten dan dapat diperkirakan membantu anak mengetahui apa yang dilakukan saat kegiatan dimulai, berlangsung, dan berakhir.",
     "contoh_pembiasaan": "Datang tepat waktu, mengikuti rutinitas pagi, mengikuti isyarat transisi, dan mengikuti prosedur pulang dengan tertib.",
     "fokus": "Kepatuhan terhadap aturan dan rutinitas", "tabel": "6.1", "halaman": 78,
     "catatan": "Bedakan mengikuti urutan kegiatan (disiplin) dengan menuntaskan tugas (tanggung jawab), agar perilaku dicatat pada butir yang tepat.",
     "indikator": [
         ("Mematuhi aturan kelas yang telah disepakati", "Kesepakatan memberi anak acuan tentang tindakan yang diharapkan. Guru mengamati apakah anak mengikuti aturan yang sudah dikenalkan, misalnya menggunakan alat bermain secara bergiliran, serta mencatat pengingat yang diperlukan.", ["PCG", "ICTG", "EDCG"]),
         ("Memulai kegiatan setelah menerima instruksi guru", "Rutinitas yang dikenal membantu anak memahami langkah pertama. Setelah guru mengajak anak menggambar, guru mengamati apakah anak mulai mengambil kertas atau memilih alat gambar.", ["PCG", "ICTG"]),
         ("Berpindah dari satu kegiatan ke kegiatan berikutnya dengan tertib", "Isyarat yang konsisten membantu anak mengenali waktu untuk beralih. Guru mengamati apakah anak mengikuti alur perpindahan dari area balok menuju meja gambar setelah mendengar lagu transisi.", ["ICTG"]),
         ("Mengikuti rangkaian kegiatan hingga selesai sesuai kesepakatan", "Fokus pengamatan terletak pada kemampuan mengikuti urutan yang telah dikenalkan. Pada kegiatan penutup, misalnya, anak mengikuti refleksi singkat, mengucapkan salam, lalu berpamitan sesuai urutan kepulangan.", ["PCG", "ICTG", "EDCG"]),
     ]},
    {"kode_aspek": "TJ", "nama": "Tanggung Jawab", "slug": "tanggung-jawab", "ikon": "hand-heart", "warna": "rose",
     "deskripsi": "Kemampuan anak menyelesaikan tugas yang menjadi kewajibannya serta menjaga lingkungan belajar sesuai aturan yang disepakati. Dalam Model PING, tanggung jawab dikembangkan melalui pembiasaan menuntaskan aktivitas sebelum berpindah ke kegiatan berikutnya.",
     "contoh_pembiasaan": "Merapikan tas dan perlengkapan, menyelesaikan kegiatan sebelum berpindah, mengembalikan alat ke tempatnya, dan membawa barang sendiri saat pulang.",
     "fokus": "Penyelesaian tugas dan kepedulian terhadap lingkungan", "tabel": "6.3", "halaman": 80,
     "catatan": "Satu kegiatan dapat memberi kesempatan mengamati dua perilaku: cara anak menjaga krayon selama kegiatan dan apakah anak mengembalikannya ke kotak setelah selesai.",
     "indikator": [
         ("Menyelesaikan tugas sesuai kemampuan", "Guru mengamati usaha anak menuntaskan tugas yang telah ia mulai, misalnya menyelesaikan bagian kolase yang menjadi tugasnya. Catatan dapat memuat langkah yang dilakukan anak dan bantuan yang membantunya melanjutkan.", ["ICTG"]),
         ("Melaksanakan tugas sederhana secara mandiri", "Pengamatan berfokus pada tingkat kemandirian. Saat datang, anak mendapat kesempatan membawa tas dan menyimpannya di tempat yang telah dikenalkan; guru mencatat apakah anak melakukannya sendiri, setelah diberi isyarat, atau bersama guru.", ["PCG", "ICTG", "EDCG"]),
         ("Menjaga barang milik sendiri dan milik sekolah", "Menjaga barang tampak selama perlengkapan digunakan, misalnya anak membuka halaman buku dengan hati-hati atau menutup kembali spidol agar dapat digunakan lagi.", ["PCG", "ICTG", "EDCG"]),
         ("Merapikan perlengkapan setelah digunakan", "Pada akhir kegiatan, guru mengamati apakah anak mengembalikan alat ke tempat yang telah disepakati, misalnya memasukkan balok ke keranjang sebelum menuju meja gambar.", ["ICTG", "EDCG"]),
     ]},
    {"kode_aspek": "KD", "nama": "Kontrol Diri", "slug": "kontrol-diri", "ikon": "heart-pulse", "warna": "fuchsia",
     "deskripsi": "Kemampuan anak mengendalikan perilaku, emosi, dan keinginan sehingga mampu bertindak sesuai aturan dan situasi. Dalam Model PING, kemampuan ini terutama diamati selama transisi ketika anak diminta menghentikan aktivitas, menunggu giliran, atau mengikuti instruksi guru.",
     "contoh_pembiasaan": "Menunggu giliran masuk kelas, berhenti saat isyarat transisi, berjalan tertib, dan tetap tenang saat menunggu dijemput.",
     "fokus": "Regulasi perilaku dan emosi", "tabel": "6.5", "halaman": 82,
     "catatan": "Catat emosi yang disampaikan anak dan cara anak mengelolanya secara terpisah. Catatan yang menggambarkan perasaan, dukungan, dan tindakan anak lebih informatif daripada ungkapan seperti “anak menolak instruksi”.",
     "indikator": [
         ("Menghentikan aktivitas ketika menerima isyarat atau instruksi guru", "Anak berlatih mengalihkan diri dari kegiatan yang sedang dinikmati. Saat isyarat akhir bermain terdengar, guru mencatat apakah anak berhenti menambah balok dan mulai memperhatikan arahan berikutnya.", ["ICTG"]),
         ("Menunggu giliran", "Kesempatan menggunakan alat bersama atau berbicara bergiliran memungkinkan guru mengamati tindakan anak selama menunggu, misalnya memberi kesempatan teman menyelesaikan ceritanya sebelum mulai berbicara.", ["PCG", "ICTG", "EDCG"]),
         ("Mengelola respons emosional ketika menghadapi kesulitan atau konflik", "Guru mencatat cara anak merespons dan memperoleh kembali ketenangannya. Ketika mainan masih digunakan teman, anak dapat menyampaikan keinginannya, meminta bantuan, atau menerima pendampingan guru untuk menunggu.", ["PCG", "ICTG", "EDCG"]),
         ("Mempertahankan perhatian selama kegiatan", "Perhatian diamati melalui keterlibatan anak dalam kegiatan, misalnya menyimak satu langkah menggambar, mencobanya, lalu kembali melanjutkan setelah perhatiannya sempat beralih.", ["PCG", "ICTG", "EDCG"]),
     ]},
    {"kode_aspek": "SS", "nama": "Sopan Santun", "slug": "sopan-santun", "ikon": "messages-square", "warna": "pink",
     "deskripsi": "Kemampuan anak menunjukkan perilaku yang menghargai orang lain melalui ucapan, sikap, dan tindakan sesuai norma sosial. Dalam Model PING, sopan santun dibiasakan melalui salam, senyum, sapaan, ucapan terima kasih, permintaan maaf, dan berpamitan.",
     "contoh_pembiasaan": "Salam, senyum, sapa, berjabat tangan, berbicara sopan, mengucapkan terima kasih, meminta maaf, dan berpamitan.",
     "fokus": "Etika komunikasi dan interaksi sosial", "tabel": "6.7", "halaman": 84,
     "catatan": "Ucapan terima kasih dan permintaan maaf dicatat sesuai kesempatan masing-masing. Bila situasi yang memerlukan permintaan maaf belum muncul, tulis “belum ada kesempatan untuk mengamati permintaan maaf”.",
     "indikator": [
         ("Mengucapkan salam ketika datang dan pulang", "Kedatangan dan kepulangan memberi kesempatan berulang untuk mengamati kebiasaan memberi salam. Guru mencatat apakah anak memulai salam atau membalas setelah disapa.", ["PCG", "EDCG"]),
         ("Menyapa dan menunjukkan sikap menghargai orang lain", "Sikap menghargai tampak ketika anak menyapa teman, menanggapi sapaan, atau memberi kesempatan orang lain selesai berbicara. Guru mencatat bentuk respons yang benar-benar ditunjukkan anak.", ["PCG", "ICTG", "EDCG"]),
         ("Mengucapkan terima kasih dan meminta maaf sesuai situasi", "Kedua ungkapan muncul pada kesempatan berbeda. Guru mencatat ucapan terima kasih setelah anak menerima bantuan dan permintaan maaf pada kesempatan lain, misalnya ketika anak menyadari tindakannya mengganggu teman.", ["PCG", "ICTG", "EDCG"]),
         ("Menggunakan bahasa yang santun saat berinteraksi", "Guru mengamati pilihan ungkapan dan kesesuaiannya dengan tujuan anak, misalnya “Boleh aku pinjam?” ketika memerlukan alat atau “Tolong bantu aku” ketika mengalami kesulitan.", ["PCG", "ICTG", "EDCG"]),
     ]},
]

REFERENSI = [
    ("Arifiyah, M. F., & Formen, A.", 2026, "Strategi pembelajaran untuk mengembangkan kemampuan menunggu giliran (turn-taking) di satuan PAUD", "jurnal", "Jurnal Obsesi: Jurnal Pendidikan Anak Usia Dini", "10", "2", "1133–1142", "10.31004/obsesi.v10i2.8232", None, None),
    ("Bandura, A.", 1986, "Social foundations of thought and action: A social cognitive theory", "buku", "Prentice-Hall", None, None, None, None, None, None),
    ("Bredekamp, S.", 2017, "Effective practices in early childhood education: Building a foundation (3rd ed.)", "buku", "Pearson", None, None, None, None, None, "Sitasi dalam teks menulis Bredekamp (2019), sedangkan daftar pustaka mencantumkan edisi 2017. Perlu konfirmasi penyusun."),
    ("Coelho, V., Azevedo, H., Teixeira, A., Soares, M., & Grande, C.", 2026, "Associations between classroom quality, child self-regulation, and psychological adjustment in early childhood", "jurnal", "Early Childhood Education Journal", "54", "6", "4747–4758", "10.1007/s10643-026-02163-2", None, None),
    ("Early Childhood Australia", 2018, "Statement on young children and digital technologies", "laporan", "Early Childhood Australia", None, None, None, "10.23965/ECA.001", None, None),
    ("International Commission on the Futures of Education", 2021, "Reimagining our futures together: A new social contract for education", "laporan", "UNESCO", None, None, None, None, "https://unesdoc.unesco.org/ark:/48223/pf0000379707", None),
    ("Lickona, T.", 1992, "Educating for character: How our schools can teach respect and responsibility", "buku", "Bantam Books", None, None, None, None, None, "Sitasi dalam teks menulis Lickona (2022), sedangkan daftar pustaka mencantumkan terbitan 1992. Perlu konfirmasi penyusun."),
    ("National Association for the Education of Young Children", 2020, "Developmentally appropriate practice [Position statement]", "laman", "NAEYC", None, None, None, None, "https://www.naeyc.org/resources/position-statements/dap/contents", None),
    ("Nur Aini, I., Mursid, M., Lia, N. F. A., & Perez, L. C. D.", 2026, "Strengthening the character of responsibility in 5–6 year old children through class agreements", "jurnal", "Jurnal Pendidikan Anak", "15", "1", "52–63", "10.21831/jpa.v15i1.2311", None, None),
    ("Organisation for Economic Co-operation and Development", 2021, "Starting strong VI: Supporting meaningful interactions in early childhood education and care", "laporan", "OECD Publishing", None, None, None, "10.1787/f47a06ae-en", None, None),
    ("Pangestuti, R., Kadiyono, A. L., Agustiani, H., & Cahyadi, S.", 2026, "Influences of sociocultural factors on Indonesian children's self-regulation and its effects on school readiness", "jurnal", "Acta Psychologica", "264", None, "Article 106393", "10.1016/j.actpsy.2026.106393", None, None),
    ("Rifai, A., & Zulkifli, N. A.", 2026, "The role of the family in instilling character education values in young children: Honesty, discipline, responsibility, and politeness", "jurnal", "Didaktik: Jurnal Ilmiah PGSD STKIP Subang", "11", "04", "290–298", "10.36989/didaktik.v11i04.9368", None, None),
    ("Sapriyanti, T.", 2026, "Penguatan pendidikan karakter di era serba digital. Dalam W. Yuliani (Ed.), Pendidikan anak usia dini di era digital: Tantangan dan inovasi pembelajaran", "bab_buku", "Lingkar Edukasi Indonesia", None, None, "145–164", None, None, None),
    ("United Nations Children's Fund", 2021, "The state of the world's children 2021: On my mind—Promoting, protecting and caring for children's mental health", "laporan", "UNICEF", None, None, None, None, "https://www.unicef.org/reports/state-worlds-children-2021", None),
    ("World Health Organization", 2019, "Guidelines on physical activity, sedentary behaviour and sleep for children under 5 years of age", "laporan", "WHO", None, None, None, None, "https://www.who.int/publications/i/item/9789241550536", None),
    ("Yusrafiddin, Murniasih, C., Juhana, & Ismail, M. R.", 2026, "Promoting religious and moral values in early childhood: A quasi-experimental study of multisensory five magic words habituation", "jurnal", "Golden Age: Jurnal Ilmiah Tumbuh Kembang Anak Usia Dini", "11", "2", "443–456", "10.14421/jga.2026.112-13", None, None),
]

NAMA_FASE = {"PCG": "Fase I", "ICTG": "Fase II", "EDCG": "Fase III"}


def tabel_html(judul: str, kepala: list[str], baris: list[list[str]], halaman: int | str) -> str:
    thead = "".join(f"<th>{E(k)}</th>" for k in kepala)
    tbody = "".join("<tr>" + "".join(f"<td>{E(str(c))}</td>" for c in r) + "</tr>" for r in baris)
    return (f"<table><caption>{E(judul)} (sumber hlm. {halaman})</caption>"
            f"<thead><tr>{thead}</tr></thead><tbody>{tbody}</tbody></table>")


def tabel_kurasi() -> dict[str, str]:
    t: dict[str, str] = {}
    f1, f2, f3 = FASE
    t["4.1"] = tabel_html("Tabel 4.1 Aktivitas Guru saat Pre-Class Greeting", ["No", "Aktivitas Guru"], [[i + 1, a] for i, a in enumerate(f1["aktivitas_guru"])], 55)
    t["4.2"] = tabel_html("Tabel 4.2 Aktivitas Anak saat Pre-Class Greeting", ["No", "Aktivitas Anak"], [[i + 1, a] for i, a in enumerate(f1["aktivitas_anak"])], 55)
    t["4.3"] = tabel_html("Tabel 4.3 Karakter Anak yang Dikembangkan saat Pre-Class Greeting", ["Karakter", "Bentuk Pembiasaan"], [[k, v] for k, v in f1["penguatan"].items()], "55–56")
    t["4.4"] = tabel_html("Tabel 4.4 Keberhasilan Pre-Class Greeting", ["Indikator", "Kriteria Keberhasilan"], [
        ["Datang tepat waktu", "≥80% anak hadir sesuai jadwal."], ["Mengucapkan salam", "≥80% anak menyapa guru."],
        ["Menjawab sapaan guru", "≥80% anak merespons positif."], ["Menyimpan barang sendiri", "≥80% anak mandiri."],
        ["Mengikuti rutinitas pagi", "≥80% anak mengikuti arahan tanpa diingatkan berulang."], ["Masuk kelas dengan tertib", "≥80% anak mengikuti transisi dengan baik."]], 57)
    nomor = 1
    for a in ASPEK:
        baris = []
        for rumusan, contoh, _ in a["indikator"]:
            baris.append([nomor, rumusan, contoh])
            nomor += 1
        t[a["tabel"]] = tabel_html(f"Tabel {a['tabel']} Indikator Perilaku {a['nama']}", ["No", "Indikator Perilaku", "Contoh Pengamatan"], baris, a["halaman"])
        bb, mb, bsh, bsb, nt, hal = RUBRIK[a["slug"]]
        t[nt] = tabel_html(f"Tabel {nt} Rubrik Penilaian Perilaku {a['nama']}", ["Skor", "Kategori", "Deskripsi"],
                           [[4, "Berkembang Sangat Baik (BSB)", bsb], [3, "Berkembang Sesuai Harapan (BSH)", bsh], [2, "Mulai Berkembang (MB)", mb], [1, "Belum Berkembang (BB)", bb]], hal)
    t["6.9"] = tabel_html("Tabel 6.9 Rekap Indikator Karakter Model Pembiasaan PING", ["Karakter", "Jumlah Indikator", "Fokus Pengamatan"],
                          [[a["nama"], 4, a["fokus"]] for a in ASPEK] + [["Total", 16, ""]], "85–86")
    baris71 = []
    nomor = 1
    for a in ASPEK:
        for rumusan, _, fase in a["indikator"]:
            teks_fase = "Seluruh fase" if len(fase) == 3 else " dan ".join(NAMA_FASE[f] for f in fase)
            baris71.append([nomor, a["nama"], rumusan, teks_fase, "", ""])
            nomor += 1
    t["7.1"] = tabel_html("Tabel 7.1 Lembar Observasi Karakter Anak (format)", ["No", "Karakter", "Indikator", "Fase Pengamatan", "Skor (1–4)", "Catatan Guru"], baris71, "87–89")
    t["7.2"] = tabel_html("Tabel 7.2 Rubrik Penskoran", ["Skor", "Kategori", "Deskripsi"], [
        [4, "Berkembang Sangat Baik (BSB)", "Anak menunjukkan perilaku secara mandiri dan konsisten pada berbagai kesempatan pengamatan."],
        [3, "Berkembang Sesuai Harapan (BSH)", "Anak sering menunjukkan perilaku yang diamati dan sesekali memerlukan pengingat."],
        [2, "Mulai Berkembang (MB)", "Anak mulai menunjukkan perilaku yang diamati dengan contoh, arahan, atau pendampingan yang masih sering diberikan."],
        [1, "Belum Berkembang (BB)", "Pada beberapa kesempatan yang sesuai, perilaku yang diamati belum tampak meskipun guru telah memberikan contoh atau pendampingan."]], "90–91")
    t["7.3"] = tabel_html("Tabel 7.3 Interpretasi Nilai", ["Persentase", "Kategori"], [["86–100", "Berkembang Sangat Baik"], ["71–85", "Berkembang Sesuai Harapan"], ["56–70", "Mulai Berkembang"], ["≤55", "Belum Berkembang"]], 92)
    t["7.4"] = tabel_html("Tabel 7.4 Rekapitulasi Hasil Observasi (format kosong)", ["No", "Nama Anak", "Disiplin", "Tanggung Jawab", "Kontrol Diri", "Sopan Santun", "Total", "%", "Kategori"], [[i, "", "", "", "", "", "", "", ""] for i in range(1, 4)], 92)
    t["7.5"] = tabel_html("Tabel 7.5 Rekap Perkembangan Karakter (format kosong)", ["Karakter", "Nomor", "Jumlah Skor", "Rata-rata", "%", "Kategori"], [["Disiplin", "1–4", "", "", "", ""], ["Tanggung jawab", "5–8", "", "", "", ""], ["Kontrol diri", "9–12", "", "", "", ""], ["Sopan santun", "13–16", "", "", "", ""]], 93)
    t["7.6"] = tabel_html("Tabel 7.6 Tindak Lanjut Guru", ["Kategori", "Tindak Lanjut"], [
        ["BSB", "Memberikan apresiasi yang menyebutkan tindakan anak secara spesifik dan menyediakan kesempatan untuk menerapkan kebiasaan tersebut dalam situasi lain."],
        ["BSH", "Melanjutkan rutinitas yang sudah dikenal anak dan memberi kesempatan untuk memulai tindakan atas inisiatif sendiri."],
        ["MB", "Memberikan contoh konkret, menyampaikan satu arahan sederhana, dan mendampingi anak saat mencoba. Dukungan disesuaikan secara bertahap berdasarkan respons anak."],
        ["BB", "Mengamati situasi yang memudahkan anak berpartisipasi, memilih satu langkah yang dapat dicoba bersama, dan berdiskusi dengan orang tua mengenai dukungan yang sesuai."]], "93–94")
    return t


def ganti_tabel_datar(htm: str, fase: dict) -> str:
    """Ganti paragraf tabel yang terdatarkan (tanpa nomor tabel) pada subbab fase II dan III."""
    htm = re.sub(r"<p>No Aktivitas Guru 1 .*?</p>", tabel_html(f"Aktivitas Guru saat {fase['nama']}", ["No", "Aktivitas Guru"], [[i + 1, a] for i, a in enumerate(fase["aktivitas_guru"])], fase["halaman"]), htm, count=1)
    htm = re.sub(r"<p>No Aktivitas (?:Guru|Anak) 1 .*?</p>", tabel_html(f"Aktivitas Anak saat {fase['nama']}", ["No", "Aktivitas Anak"], [[i + 1, a] for i, a in enumerate(fase["aktivitas_anak"])], fase["halaman"]), htm, count=1)
    htm = re.sub(r"<p>Karakter Bentuk Penguatan .*?</p>", tabel_html(f"Penguatan Karakter pada {fase['nama']}", ["Karakter", "Bentuk Penguatan"], [[k, v] for k, v in fase["penguatan"].items()], fase["halaman"]), htm, count=1)
    htm = re.sub(r"<h3>10\. Indikator Keberhasilan Kriteria</h3>\s*<p>Indikator ≥80%</p>\s*<p>.*?</p>",
                 "<h3>10. Indikator Keberhasilan</h3>" + tabel_html(f"Indikator Keberhasilan {fase['nama']}", ["Kriteria"], [[k] for k in fase["indikator_keberhasilan"]], fase["halaman"]), htm, count=1, flags=re.S)
    return htm


def figur(nomor: str, judul: str, halaman: int) -> str:
    return (f'<aside class="rujukan-sumber"><p><strong>Gambar {E(nomor)}</strong> {E(judul)}. '
            f"Ilustrasi ini tersedia pada dokumen PDF modul (hlm. {halaman}).</p></aside>")


def kurasi_bab_viii(bab: dict) -> None:
    """Halaman sumber 95 memuat teks tumpang tindih; susun ulang subbab A dan B."""
    a, b = bab["subbab"][0], bab["subbab"][1]
    htm = a["html"]
    potong = htm.find("Guru berdiri di depan kelas menyambut setiap anak.")
    sisa = "<p>" + htm[potong:] if potong >= 0 else ""
    identitas = tabel_html("Identitas Pembelajaran", ["Komponen", "Keterangan"], [
        ["Kelompok", "B (Usia 5–6 Tahun)"], ["Tema", "Lingkunganku"], ["Subtema", "Menjaga Kebersihan Kelas"],
        ["Waktu", "90 Menit"], ["Model", "Pembiasaan PING"], ["Fokus Karakter", "Disiplin, Tanggung Jawab, Kontrol Diri, Sopan Santun"]], 95)
    alur = tabel_html("Alur Pembelajaran", ["Tahap", "Kegiatan", "Fase Model PING", "Karakter"], [
        ["Kedatangan", "Guru menyambut anak", "Pre-Class Greeting", "Disiplin, Sopan Santun"],
        ["Pembukaan", "Berdoa, bernyanyi", "Pre-Class Greeting", "Disiplin"],
        ["Kegiatan Inti", "Bermain dan belajar", "Inter-Class Transition Greeting", "Tanggung Jawab, Kontrol Diri"],
        ["Transisi", "Merapikan alat, berpindah kegiatan", "Inter-Class Transition Greeting", "Tanggung Jawab"],
        ["Penutup", "Refleksi dan salam", "End-Day Closing Greeting", "Sopan Santun"]], 95)
    a["html"] = ("<p>Contoh skenario berikut menunjukkan cara mengintegrasikan ketiga fase PING ke dalam satu hari "
                 "pembelajaran kelompok B dengan tema Lingkunganku.</p>" + identitas + alur +
                 '<aside class="rujukan-sumber"><p>Tabel alur pembelajaran disusun ulang karena tata letak halaman sumber '
                 "tumpang tindih; periksa kembali dengan dokumen PDF (hlm. 95).</p></aside>")
    sisa = re.sub(r"\s*Contoh (\d)\s*:\s*((?:Pre-Class|Inter-Class Transition|End-Day Closing) Greeting)\s*", r"</p><h3>Contoh \1: \2</h3><p>", sisa)
    sisa = sisa.replace("<p></p>", "")
    sisa = re.sub(r"</p>\n<p>bersama\.\"</p>", ' bersama."</p>', sisa)
    b["html"] = "<h3>Contoh 1: Pre-Class Greeting</h3>" + sisa
    b.update({"halaman_pdf_awal": a["halaman_pdf_awal"], "halaman_pdf_akhir": a["halaman_pdf_akhir"],
              "halaman_cetak_awal": a["halaman_cetak_awal"], "halaman_cetak_akhir": a["halaman_cetak_akhir"],
              "tabel": [], "gambar": [], "tidak_ditemukan": False})
    a["halaman_cetak_akhir"] = a["halaman_cetak_awal"]
    d = bab["subbab"][3]
    d["html"] = re.sub(r"<p>\.{5,}[^<]*</p>", "<p><em>(Diisi guru sesuai kendala yang ditemui.)</em></p>", d["html"])
    c = bab["subbab"][2]
    c["html"] = c["html"].replace("(End-Day Closing.", "(End-Day Closing Greeting).").replace("<p>Greeting) Analisis Implementasi", "<p>Analisis Implementasi")


def main(masuk: str, keluar: str) -> None:
    data = json.load(open(masuk, encoding="utf-8"))
    kurasi_bab_viii(data["bab"][7])
    tabel = tabel_kurasi()
    belum_dikurasi: list[str] = []
    for bab in data["bab"]:
        for sub in bab["subbab"]:
            htm = sub["html"]
            if bab["nomor_bab"] == 4 and sub["huruf"] in "BC":
                htm = ganti_tabel_datar(htm, FASE["ABC".index(sub["huruf"])])
            for t in sub.get("tabel", []):
                pengganti = tabel.get(t["nomor"])
                if not pengganti:
                    belum_dikurasi.append(t["nomor"])
                    pengganti = (f'<aside class="rujukan-sumber"><p><strong>Tabel {E(t["nomor"])}</strong> {E(t["judul"])}. '
                                 f"Tabel lengkap tersedia pada dokumen PDF modul (hlm. {t['halaman']}).</p></aside>")
                htm = htm.replace(f"<!--TABEL:{t['nomor']}-->", pengganti)
            for g in sub.get("gambar", []):
                htm = htm.replace(f"<!--GAMBAR:{g['nomor']}-->", figur(g["nomor"], g["judul"], g["halaman"]))
            sub["html"] = htm.strip()
    fase_keluar = []
    for f in FASE:
        fase_keluar.append({**{k: v for k, v in f.items() if k not in ("langkah", "dialog_fase")},
                            "contoh_dialog": [{"pembicara": p, "ucapan": u} for p, u in f["dialog_fase"]],
                            "langkah": [{"nomor_langkah": i + 1, "judul": j, "penjelasan": pj, "aktivitas_guru": ag, "aktivitas_anak": aa,
                                         "contoh_dialog": [{"pembicara": p, "ucapan": u} for p, u in d]}
                                        for i, (j, pj, ag, aa, d) in enumerate(f["langkah"])]})
    aspek_keluar = []
    nomor = 1
    for a in ASPEK:
        bb, mb, bsh, bsb, _, _ = RUBRIK[a["slug"]]
        indikator = []
        for rumusan, contoh, fase in a["indikator"]:
            indikator.append({"nomor_indikator": nomor, "rumusan": rumusan, "contoh_pengamatan": contoh, "fase_pengamatan": fase,
                              "catatan_pendampingan": a["catatan"], "rubrik_bb": bb, "rubrik_mb": mb, "rubrik_bsh": bsh, "rubrik_bsb": bsb})
            nomor += 1
        aspek_keluar.append({k: v for k, v in a.items() if k not in ("indikator", "tabel", "halaman", "catatan")} | {"indikator": indikator})
    referensi = [{"penulis": p, "tahun": th, "judul": j, "jenis_sumber": js, "nama_penerbit_jurnal": pj, "volume": v, "nomor_terbitan": n,
                  "halaman": h, "doi": doi, "url": url or (f"https://doi.org/{doi}" if doi else None), "catatan_editorial": c}
                 for p, th, j, js, pj, v, n, h, doi, url, c in REFERENSI]
    keluaran = {
        "sumber": data["sumber"], "jumlah_halaman_pdf": data["jumlah_halaman_pdf"],
        "selisih_halaman_cetak": data["selisih_halaman_cetak"],
        "catatan_editorial": [
            "Daftar tabel menyebut 'Tabel 9.3 Refleksi Implementasi', sedangkan isi bab memakai 'Tabel 9.4 Refleksi Implementasi'.",
            "Judul kolom tabel Aktivitas Anak pada fase Inter-Class Transition Greeting tertulis 'Aktivitas Guru' pada sumber.",
            "Sitasi Lickona (2022) dan Bredekamp (2019) dalam teks berbeda tahun dengan daftar pustaka (1992 dan 2017).",
            "Halaman 95 PDF (hlm. cetak 95) memuat teks tumpang tindih pada judul 'B. Contoh Kegiatan'; subbab VIII.B disusun ulang dari teks yang terbaca.",
        ],
        "tabel_belum_dikurasi": sorted(set(belum_dikurasi)),
        "bab": data["bab"], "fase": fase_keluar, "aspek": aspek_keluar, "referensi": referensi,
    }
    json.dump(keluaran, open(keluar, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    print("Tabel belum dikurasi (dirujuk ke PDF):", keluaran["tabel_belum_dikurasi"])


if __name__ == "__main__":
    if len(sys.argv) != 3:
        print(__doc__)
        sys.exit(1)
    main(sys.argv[1], sys.argv[2])
