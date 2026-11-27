# Riwayat Perubahan

Semua perubahan penting pada proyek ini dicatat di sini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan penomoran [Semantic Versioning](https://semver.org/lang/id/).

## [1.0.0] — 2026-11-27

Rilis publik pertama.

### Ditambahkan

- **Website publik (tanpa login)**: beranda dinamis, 9 bab & 34 subbab materi PING dalam HTML (Inertia SSR), 3 fase sapaan & 17 langkah, 4 aspek karakter & 16 indikator, FAQ, galeri, tentang & profil penyusun, panduan penggunaan, kebijakan privasi, pencarian materi, formulir kontak (honeypot, rate limit, persetujuan privasi).
- **Pembaca bab**: daftar isi lengket/drawer, ukuran huruf & lebar baca, penanda bab, posisi baca terakhir, salin/bagikan tautan, tema terang/gelap/sistem.
- **Dokumen PDF**: penampil PDF.js malas (permintaan Range 206), **mode buku/flipbook** dengan animasi balik 3D (dua halaman di layar lebar, satu di ponsel, klik/geser/panah/slider/layar penuh), mode standar dengan zoom, izin unduh global & per dokumen.
- **Panel admin** (satu pengelola, login + captcha aritmetika, reset sandi bertoken): dashboard & grafik, bab/subbab dengan editor Tiptap, versi dokumen PDF, fase & langkah, aspek & indikator, beranda dinamis, halaman informasi, pustaka media, galeri, FAQ, referensi, menu & tautan sosial, pengalihan URL, statistik + CSV, log audit, pengaturan (identitas, logo persegi panjang terang/gelap, kontak, tampilan, SEO, dokumen, privasi), profil, keamanan.
- **Pesan masuk**: tab kotak masuk/arsip/status, menu aksi per pesan, aksi massal (dibaca, belum dibaca, ditindaklanjuti, arsip/pulihkan, hapus), balas lewat aplikasi surel dengan kutipan, navigasi antar pesan, riwayat, lencana jumlah pesan baru di menu.
- **SEO**: SSR, canonical absolut, Open Graph/Twitter Card, JSON-LD (WebSite, Organization/Person, BreadcrumbList, LearningResource, FAQPage), `sitemap.xml`, `robots.txt` dinamis, pengalihan 301 saat slug berubah.
- **Statistik pihak pertama** berbasis sidik HMAC (IP mentah tidak disimpan), deduplikasi harian, data demo terpisah, retensi otomatis.
- **Keamanan**: CSP bernonce, header keamanan, sanitasi HTML, validasi PDF/gambar berbasis isi, rate limit, audit log tanpa isi pesan.
- **Database berbahasa Indonesia**: 23 tabel + `riwayat_migrasi` (repositori migrasi kustom), MySQL 8.4.
- **Operasional**: `deploy/deploy.sh` (rilis tanpa downtime), layanan SSR systemd/Supervisor, cron retensi & cadangan harian (`deploy/cadangan.sh`), ekspor data lokal (`scripts/ekspor-data-lokal.ps1`) & impor ke server (`deploy/impor-data.sh`), perintah `ping:hapus-demo`, `ping:admin-awal`, `ping:periksa-berkas`.
- **Dokumentasi**: README, [panduan deploy](docs/deploy.md), analisis sistem, arsitektur, kamus data, status pengujian.
- **Kualitas**: 59 tes PHPUnit di MySQL, Pint, oxlint/oxfmt (Vite+), TypeScript strict, CI GitHub Actions.

### Keamanan

- `pdfjs-dist` dinaikkan ke 6.5.438 untuk menutup GHSA-hq66-cqwq-w95j (eksekusi JavaScript lewat PDF berbahaya).

[1.0.0]: https://github.com/yaridwan/e-modelping/releases/tag/v1.0.0
