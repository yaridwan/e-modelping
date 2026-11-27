# Kebijakan Keamanan

## Versi yang didukung

| Versi | Dukungan keamanan |
| ----- | ----------------- |
| 1.x   | ✔                 |
| < 1.0 | ✘                 |

## Melaporkan celah keamanan

**Jangan** melaporkan celah keamanan lewat issue publik, diskusi, atau pull request.

Gunakan fitur **[Report a vulnerability](https://github.com/yaridwan/e-modelping/security/advisories/new)** (tab _Security_ → _Advisories_) di repositori ini. Sertakan:

- jenis celah dan dampaknya,
- langkah reproduksi (URL/rute, data contoh, versi),
- bukti konsep bila ada,
- saran perbaikan bila ada.

Anda akan menerima tanggapan awal dalam **7 hari**. Setelah perbaikan dirilis, laporan dapat dipublikasikan dengan kredit untuk pelapor (bila diinginkan).

## Cakupan

Termasuk: kode aplikasi di repositori ini (Laravel, React, skrip `deploy/` dan `scripts/`).
Tidak termasuk: konfigurasi server milik pemasang, layanan pihak ketiga, serta serangan yang memerlukan akses admin yang sah atau akses fisik ke server.

## Langkah keamanan yang sudah diterapkan

- Login admin tunggal dengan captcha, pembatasan percobaan, regenerasi sesi, reset sandi bertoken HMAC sekali pakai; tidak ada registrasi publik.
- Content-Security-Policy bernonce, `X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`, HSTS di HTTPS.
- Sanitasi HTML (symfony/html-sanitizer) untuk konten kaya; URL tautan & embed video divalidasi.
- Validasi unggahan berbasis isi: PDF (signature, tanpa enkripsi/JavaScript/Launch/EmbeddedFile), gambar diproses ulang GD; SVG ditolak. **Bukan pemindai malware.**
- Berkas unggahan disimpan privat dan dilayani lewat rute aplikasi; `.env`, kode, dan storage di luar document root.
- Statistik tanpa IP mentah (sidik HMAC), audit log tanpa isi pesan, ekspor CSV aman dari _formula injection_.
- Dependensi diperiksa dengan `composer audit` dan `npm audit` (CI & sebelum rilis).

## Saran untuk pemasang

- `APP_DEBUG=false`, `APP_ENV=production`, HTTPS + `SESSION_SECURE_COOKIE=true`.
- Hapus `ADMIN_AWAL_SANDI` dari `.env` setelah akun admin dibuat; gunakan sandi kuat.
- Perbarui OS, PHP, Node, dan dependensi secara berkala; simpan cadangan di luar server.
