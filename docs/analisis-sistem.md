# Analisis Sistem — E-Model PING

## Masalah yang dilayani

Guru PAUD membutuhkan panduan praktis untuk membiasakan disiplin, tanggung jawab, kontrol diri, dan sopan santun anak usia 5–6 tahun pada momen harian (kedatangan, perpindahan kegiatan, kepulangan). Materi E-Model Pembiasaan PING tersedia sebagai PDF 121 halaman; website ini menyajikannya sebagai e-modul HTML yang dapat dibaca, dicari, dan diindeks, dengan PDF sebagai dokumen pendamping.

## Batas sistem

| Termasuk                                                                                                                  | Tidak termasuk                                          |
| ------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------- |
| Landing page publik, 9 bab + subbab HTML, 3 fase, 4 aspek & 16 indikator (referensi), FAQ, galeri, kontak, pencarian HTML | Registrasi publik, akun guru, dashboard guru, langganan |
| Satu akun pemilik/admin (CMS penuh)                                                                                       | Multi-tenant, marketplace, LMS berbayar, RBAC kompleks  |
| Statistik pihak pertama berpseudonim                                                                                      | Pelacak pihak ketiga, klaim efektivitas penelitian      |
| Instrumen observasi sebagai **materi**                                                                                    | Penyimpanan data/skor/foto anak secara daring           |

## Aktor & peran

| Kemampuan                                                                                                     | Pengunjung/guru                       | Admin                |
| ------------------------------------------------------------------------------------------------------------- | ------------------------------------- | -------------------- |
| Membaca materi terbit, PDF, mencari, mengirim pesan                                                           | ✔ (tanpa login)                       | ✔                    |
| Mengunduh PDF                                                                                                 | ✔ bila diizinkan global & per dokumen | ✔ (pratinjau privat) |
| Melihat draf/arsip, pratinjau                                                                                 | ✘                                     | ✔                    |
| Kelola bab/subbab, dokumen, fase, karakter, beranda, halaman, media, galeri, FAQ, referensi, menu, pengalihan | ✘                                     | ✔                    |
| Pesan masuk, statistik, log audit (baca saja), pengaturan, profil, keamanan                                   | ✘                                     | ✔                    |

## Peta halaman publik

`/`, `/tentang`, `/modul`, `/modul/{slug}`, `/fase/{slug}`, `/karakter`, `/panduan-penggunaan`, `/galeri`, `/pertanyaan-umum`, `/kontak` (GET/POST), `/kebijakan-privasi`, `/cari?q=`, `/dokumen/{versi}/baca` (halaman penampil), `/dokumen/{versi}/berkas` (PDF inline, Range), `/dokumen/{versi}/unduh`, `/media/{uuid}.{ext}`, `/sitemap.xml`, `/robots.txt`, `/{slug}` (halaman informasi tambahan, mis. `/pesan-untuk-guru-paud`), `/admin/masuk`, `/admin/lupa-sandi`, `/admin/atur-ulang-sandi/{token}`.

## Alur utama

### Pembaca publik

```mermaid
flowchart TD
    A["Pengunjung membuka website"] --> B["Landing page publik (SSR)"]
    B --> C{"Pilih kebutuhan"}
    C --> D["Jelajahi sembilan bab"]
    C --> E["Kenali fase dan karakter"]
    D --> F["Baca penjelasan bab (HTML)"]
    E --> F
    F --> G{"PDF terbit tersedia?"}
    G -->|Ya| H["Baca PDF (PDF.js, Range 206) atau unduh jika diizinkan"]
    G -->|Tidak| I["Lanjutkan materi HTML; tombol dokumen nonaktif"]
    H --> J["Pilih bab berikutnya (hanya bab terbit)"]
    I --> J
```

### Admin dan captcha

```mermaid
flowchart TD
    A["Buka /admin/masuk"] --> B["Isi surel, sandi, jawaban captcha"]
    B --> C{"Batas percobaan & captcha valid?"}
    C -->|Tidak| D["Pesan aman + tantangan baru"]
    D --> B
    C -->|Ya| E{"Kredensial & akun aktif?"}
    E -->|Tidak| D
    E -->|Ya| F["Regenerasi sesi → dashboard"]
    F --> G{"Pilih pengelolaan"}
    G --> H["Konten & dokumen"]
    G --> I["Identitas & profil"]
    G --> J["Statistik & pesan"]
```

### Unggah dan publikasi PDF

```mermaid
flowchart TD
    A["Admin memilih bab & mengunggah PDF"] --> B{"Lolos validasi? (ukuran, MIME, signature, %%EOF, tanpa enkripsi/JS, parser)"}
    B -->|Tidak| C["Tolak + alasan per field"]
    B -->|Ya| D["Simpan privat (storage/app/private) sebagai versi draf"]
    D --> E["Pratinjau admin (no-store, noindex)"]
    E --> F{"Siap terbit?"}
    F -->|Belum| D
    F -->|Ya| G["Transaksi + row lock lingkup → aktifkan"]
    G --> H["Arsipkan versi aktif sebelumnya"]
    H --> I["Naikkan versi cache publik, sitemap, log audit"]
    I --> J["Publik hanya menerima versi aktif terbaru"]
```

## ERD ringkas

```mermaid
erDiagram
    pengguna ||--o{ berkas_media : "diunggah_oleh"
    pengguna |o--o| berkas_media : "foto_profil_id"
    pengguna ||--o{ token_atur_ulang_sandi : memiliki
    pengguna ||--o{ log_aktivitas : pelaku
    bab_modul ||--|{ subbab_modul : berisi
    bab_modul ||--o{ versi_dokumen : "dokumen bab"
    berkas_media ||--o{ versi_dokumen : berkas
    versi_dokumen ||--o{ aktivitas_dokumen : statistik
    bab_modul ||--o{ fase_ping : "dibahas di"
    fase_ping ||--|{ langkah_fase : langkah
    aspek_karakter ||--|{ indikator_karakter : "4 indikator"
    bab_modul ||--o{ kunjungan_halaman : statistik
    halaman_informasi ||--o{ menu_navigasi : target
    bab_modul ||--o{ menu_navigasi : target
    menu_navigasi ||--o{ menu_navigasi : submenu
    berkas_media ||--o{ galeri_kegiatan : gambar
    berkas_media ||--o{ bagian_beranda : media
    pengaturan_website }o--o| berkas_media : "logo/favicon/og"
```

Tabel tanpa relasi: `pertanyaan_umum`, `tautan_sosial`, `referensi_pustaka`, `pesan_kontak`, `pengalihan_url`.

## Aturan bisnis kunci

1. Akses publik selalu memakai _scope_ terbit di server (bab, subbab, halaman, fase, galeri, versi dokumen + bab induk terbit).
2. PDF di penyimpanan privat; endpoint publik memeriksa terbit + aktif + bab induk terbit; unduh memeriksa izin global **dan** per dokumen. Menonaktifkan unduh bukan DRM.
3. Satu versi aktif per lingkup: indeks unik kolom tergenerasi + transaksi + `SELECT … FOR UPDATE`.
4. Perubahan slug → 301 dari URL lama; pengalihan diratakan (tanpa rantai) dan dicegah loop.
5. Struktur inti: 9 bab dan 3 fase tidak dapat dibuat/dihapus; bab menyisakan ≥1 subbab; fase ≥1 langkah; halaman inti tidak dapat dihapus.
6. Media yang masih dirujuk tidak dapat dihapus; gambar hanya publik bila dirujuk konteks terbit.
7. Statistik: deduplikasi harian, bot/admin dikecualikan, data demo terpisah, retensi terjadwal.
