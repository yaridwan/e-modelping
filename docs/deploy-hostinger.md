# Panduan Deploy E-Model PING ke Hostinger (Shared Hosting)

Panduan ini untuk paket **shared hosting Hostinger** (Premium, Business, atau setara) yang sudah Anda miliki — **tanpa membeli VPS**. Semua langkah memakai hPanel dan SSH bawaan paket. Perkiraan waktu pertama kali: 30–60 menit.

> Punya VPS? Pakai [docs/deploy.md](deploy.md) (lengkap dengan SSR Node). Perbedaannya dijelaskan di [§1](#1-apa-bedanya-dengan-vps).

## Daftar isi

1. [Apa bedanya dengan VPS](#1-apa-bedanya-dengan-vps)
2. [Gambaran alur](#2-gambaran-alur)
3. [Persiapan di hPanel](#3-persiapan-di-hpanel)
4. [Buat paket di komputer](#4-buat-paket-di-komputer)
5. [Unggah paket ke server](#5-unggah-paket-ke-server)
6. [Pasang pertama kali (SSH)](#6-pasang-pertama-kali-ssh)
7. [Arahkan `public_html` ke aplikasi](#7-arahkan-public_html-ke-aplikasi)
8. [Isi website: data baru atau data lokal](#8-isi-website-data-baru-atau-data-lokal)
9. [Cron: retensi & cadangan](#9-cron-retensi--cadangan)
10. [Verifikasi](#10-verifikasi)
11. [Memperbarui website](#11-memperbarui-website)
12. [Pemecahan masalah](#12-pemecahan-masalah)

---

## 1. Apa bedanya dengan VPS

| Hal                         | VPS ([deploy.md](deploy.md)) | Hostinger shared (panduan ini)                                                                                                                  |
| --------------------------- | ---------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| SSR (HTML awal berisi teks) | ✔ server Node                | ✘ dimatikan — isi halaman dirender di browser. **Judul, deskripsi, gambar pratinjau WhatsApp/Facebook, sitemap & JSON-LD tetap dibuat server.** |
| Database                    | MySQL 8.4                    | MariaDB (bawaan Hostinger) — **diuji**: 59 tes lulus di MariaDB 10.4, CI juga menguji MariaDB 10.11 & 11.4                                      |
| Build aset & Composer       | di server (`deploy.sh`)      | di komputer Anda (`scripts/paket-hostinger.ps1`) → unggah satu berkas ZIP                                                                       |
| Rilis tanpa downtime        | ✔                            | ✔ (folder `releases/` + symlink `current`, sama seperti VPS)                                                                                    |
| Cadangan                    | cron `cadangan.sh`           | cron `cadangan.sh` (hPanel) **ditambah** cadangan otomatis Hostinger                                                                            |
| Surel "Lupa sandi"          | SMTP pihak ketiga            | ✔ surel Hostinger (`smtp.hostinger.com`)                                                                                                        |
| Biaya                       | VPS terpisah                 | tidak ada biaya tambahan                                                                                                                        |

Kebutuhan paket: PHP **8.4 atau 8.5** (tersedia di hPanel), akses SSH, database MySQL/MariaDB, dan dukungan symlink untuk `public_html` (didukung Hostinger).

## 2. Gambaran alur

```text
Komputer Anda (Windows)                                  Server Hostinger
──────────────────────                                   ────────────────────────────────────────────────
scripts\paket-hostinger.ps1  ──► paket .zip ──unggah──►  pasang-rilis.sh  ──► ~/e-modelping/
scripts\ekspor-data-lokal.ps1 ─► data .tar.gz ─unggah─►  impor-data.sh (opsional, data lokal)
                                                         ~/domains/DOMAIN/public_html ──symlink──► ~/e-modelping/current/public
                                                         Cron hPanel: schedule:run + cadangan.sh
```

Struktur di server (contoh nyata e-modelping.com):

```text
/home/u123456789/
├── e-modelping/                                # aplikasi (di LUAR ~/domains)
│   ├── current -> releases/20261127091129      # rilis aktif
│   ├── releases/                               # 3 rilis terakhir
│   ├── shared/.env                             # rahasia produksi (izin 600)
│   ├── shared/storage/                         # unggahan, log, cache, sesi
│   └── cadangan/                               # db-*.sql.gz, media-*.tar.gz (izin 700)
└── domains/domain-anda.com/
    ├── DO_NOT_UPLOAD_HERE                      # penanda Hostinger: jangan taruh berkas di sini
    ├── public_html -> /home/u123456789/e-modelping/current/public   # symlink (dibuat sekali)
    └── public_html.lama/                       # isi bawaan Hostinger (boleh dihapus)
```

Aplikasi sengaja diletakkan di `~/e-modelping`, bukan di `~/domains/<domain>/`, karena Hostinger menaruh penanda `DO_NOT_UPLOAD_HERE` di folder domain. Hanya `public_html` yang diarahkan ke aplikasi.

## 3. Persiapan di hPanel

Semua menu di bawah ada di **hPanel → Website → (pilih website) → Kelola**.

### 3.1 Pilih domain & buat website

Tentukan alamat website, misalnya domain baru (`domain-anda.com`) atau subdomain (`ping.yaridwan.com`).

- **Domain baru/yang sudah terdaftar tanpa website:** hPanel → **Website → PHP/HTML → Buat website** → pilih domain → website kosong.
- **Subdomain:** hPanel → Website domain induk → **Domain → Subdomain** → buat `ping`. Catat foldernya (biasanya `~/domains/yaridwan.com/public_html/ping`); untuk subdomain, sesuaikan jalur di langkah §6–§7 dengan folder tersebut.

### 3.2 PHP 8.4

**Lanjutan → Konfigurasi PHP** (`hpanel.hostinger.com/websites/DOMAIN/advanced/php-configuration`):

1. Tab **Versi PHP** → pilih **PHP 8.4** (atau 8.5) → **Update**. Perubahan butuh 1–2 menit.
2. Tab **Ekstensi PHP** → pastikan aktif: `pdo_mysql`, `mbstring`, `gd`, `intl`, `zip`, `bcmath`, `fileinfo`, `openssl`, `ctype`, `dom`.
3. Tab **Opsi PHP** → `upload_max_filesize` **32M**, `post_max_size` **32M**, `memory_limit` **256M**, `max_execution_time` **60**.

### 3.3 Database

**Database → Database → Tambah database**: isi nama (mis. `emodelping`), pengguna, dan sandi kuat. Hostinger menambahkan awalan akun, mis. `u123456789_emodelping`. Catat **nama database, nama pengguna, dan sandi** — dipakai di `.env`. Host database: `localhost`.

### 3.4 SSH

**Lanjutan → Akses SSH** (`…/advanced/ssh-access`): pastikan status **ACTIVE**, catat **IP**, **port 65002**, dan **username** (`u123456789`). Atur sandi SSH (tombol _Ubah_) atau tambahkan SSH key (lebih aman).

### 3.5 Surel pengirim

**Email** → buat akun, mis. `noreply@domain-anda.com` (dipakai untuk surel "Lupa sandi"). Catat sandinya.

### 3.6 SSL

**Keamanan → SSL**: pastikan sertifikat aktif (Hostinger memasangnya otomatis untuk domain/subdomain) dan aktifkan **paksa HTTPS** bila tersedia.

## 4. Buat paket di komputer

Di folder proyek (PowerShell), pastikan perubahan sudah di-commit, lalu:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\paket-hostinger.ps1
```

Skrip melakukan `npm run build`, menyalin berkas aplikasi (tanpa tes, dokumen, sumber React, `.env`), memasang `vendor` **tanpa paket dev**, lalu membuat `paket\e-model-ping-hostinger-YYYYMMDD-HHMMSS.zip` (±16 MB). Paket tidak berisi `.env`, unggahan, log, maupun data database.

## 5. Unggah paket ke server

Pilih salah satu:

- **SCP (disarankan):**
    ```powershell
    scp -P 65002 paket\e-model-ping-hostinger-YYYYMMDD-HHMMSS.zip u123456789@IP-SERVER:~/
    ```
- **File Manager hPanel:** **File → File Manager** → buka folder akun (direktori paling atas, `/home/u123456789`) → **Upload** ZIP. Jangan diekstrak — skrip yang mengekstraknya.

## 6. Pasang pertama kali (SSH)

```bash
ssh -p 65002 u123456789@IP-SERVER
php -v                      # harus 8.4.x atau 8.5.x
```

Di Hostinger, `php` CLI bawaan bisa **tetap 8.3** walau website sudah diatur ke 8.4 (terjadi pada e-modelping.com). PHP 8.4 tersedia di **`/opt/alt/php84/usr/bin/php`** — semua perintah di panduan ini memakainya. Cek:

```bash
/opt/alt/php84/usr/bin/php -v      # PHP 8.4.x
ls /opt/alt/php8*/usr/bin/php      # versi lain yang tersedia
```

Ambil skrip pemasang dari paket, lalu jalankan (ganti `domain-anda.com` dan nama ZIP):

```bash
cd ~
unzip -q -o e-model-ping-hostinger-*.zip 'e-model-ping/deploy/hostinger/pasang-rilis.sh' -d ~/alat-ping
APP=~/e-modelping DOMAIN=domain-anda.com PHP=/opt/alt/php84/usr/bin/php bash ~/alat-ping/e-model-ping/deploy/hostinger/pasang-rilis.sh ~/e-model-ping-hostinger-YYYYMMDD-HHMMSS.zip
```

**Jalankan pertama** berhenti dengan pesan bahwa `~/e-modelping/shared/.env` dibuat dari templat (`APP_KEY` sudah diisi otomatis). Lengkapi isinya:

```bash
nano ~/e-modelping/shared/.env
```

| Kunci                                                     | Isi                                                                                   |
| --------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| `APP_URL`                                                 | `https://domain-anda.com` (pakai https)                                               |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`               | dari §3.3 (`DB_CONNECTION=mariadb`, `DB_HOST=localhost`)                              |
| `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`     | akun surel §3.5 (alamat pengirim = akun SMTP)                                         |
| `ADMIN_AWAL_SUREL`, `ADMIN_AWAL_NAMA`, `ADMIN_AWAL_SANDI` | hanya bila memulai dari data baru (§8 pilihan A); kosongkan bila mengimpor data lokal |

Simpan (`Ctrl+O` atau `F3`, `Enter`, lalu `Ctrl+X`) — pastikan muncul `[ Wrote … lines ]`. Lalu **jalankan perintah pemasang yang sama sekali lagi** (tambahkan `LEWATI_SEED=1` bila Anda akan mengimpor data lokal di §8 pilihan B). Kali ini skrip: memeriksa PHP & ekstensi → mengekstrak rilis (izin dirapikan ke 755/644, karena ZIP dari Windows tidak membawa izin Unix) → migrasi database → data dasar (9 bab, PDF, pengaturan, admin) → cache produksi → mengaktifkan rilis. Di akhir, skrip menampilkan perintah untuk §7.

## 7. Arahkan `public_html` ke aplikasi

Sekali saja (folder `public_html` bawaan disimpan sebagai `public_html.lama`):

```bash
cd ~/domains/domain-anda.com
mv public_html public_html.lama
ln -s ~/e-modelping/current/public public_html
ls -l public_html          # public_html -> /home/u123456789/e-modelping/current/public
```

Buka `https://domain-anda.com` — beranda E-Model PING tampil. Setelah yakin berjalan, `public_html.lama` boleh dihapus.

## 8. Isi website: data baru atau data lokal

**Pilihan A — mulai dari data dasar.** Login di `https://domain-anda.com/admin/masuk` memakai `ADMIN_AWAL_SUREL` + `ADMIN_AWAL_SANDI`, lalu **hapus nilai `ADMIN_AWAL_SANDI`** dari `.env` dan bangun ulang cache:

```bash
cd ~/e-modelping/current && /opt/alt/php84/usr/bin/php artisan config:cache
```

Isi logo, sampul, kontak, dan konten lain lewat menu admin.

**Pilihan B — pindahkan data lokal** (logo, sampul, galeri, pengaturan, suntingan bab, pesan) dari komputer Anda:

```powershell
# Komputer (MySQL lokal berjalan)
powershell -ExecutionPolicy Bypass -File scripts\ekspor-data-lokal.ps1
scp -P 65002 cadangan\e-model-ping-data-YYYYMMDD-HHMMSS.tar.gz u123456789@IP-SERVER:~/
```

```bash
# Server
APP=~/e-modelping PHP=/opt/alt/php84/usr/bin/php bash ~/e-modelping/current/deploy/impor-data.sh ~/e-model-ping-data-YYYYMMDD-HHMMSS.tar.gz
rm ~/e-model-ping-data-*.tar.gz          # arsip berisi data pribadi
```

Skrip mencadangkan database server dulu, mengganti isinya dengan data lokal, menyalin media, menjalankan migrasi, menghapus data demo, dan membangun ulang cache. Ekspor MySQL 8.4 → impor MariaDB sudah **diuji: 24 tabel identik**. Login memakai surel & sandi admin lokal Anda, lalu ganti sandinya di menu **Keamanan**.

> Tanpa perintah `mysql` di SSH? Impor `database.sql` (di dalam arsip) lewat **phpMyAdmin** (hPanel → Database → phpMyAdmin → Import), unggah folder `media/` ke `~/e-modelping/shared/storage/app/private/media/` lewat File Manager, lalu jalankan `/opt/alt/php84/usr/bin/php artisan ping:hapus-demo --force && /opt/alt/php84/usr/bin/php artisan optimize:clear && /opt/alt/php84/usr/bin/php artisan config:cache` di folder `current`.

## 9. Cron: retensi & cadangan

Jam server Hostinger memakai **UTC** (WIB − 7 jam). SSH Hostinger **tidak menyediakan `crontab`**, jadi cron dibuat lewat hPanel → **Lanjutan → Cron Job** → jenis **Custom**. Ganti `u123456789` dengan username SSH Anda (perintah memakai PHP 8.4 dari §6):

| Jadwal                                           | Perintah                                                                                                                                                   |
| ------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Setiap menit (`* * * * *`)                       | `cd /home/u123456789/e-modelping/current && /opt/alt/php84/usr/bin/php artisan schedule:run >> /dev/null 2>&1`                                             |
| Harian 01.30 WIB (`30 18 * * *`, jam server UTC) | `APP=/home/u123456789/e-modelping bash /home/u123456789/e-modelping/current/deploy/cadangan.sh >> /home/u123456789/e-modelping/cadangan/cadangan.log 2>&1` |

`schedule:run` menjalankan `ping:bersihkan-retensi` setiap hari (statistik & pesan lewat masa retensi). `cadangan.sh` menyimpan `db-*.sql.gz` + `media-*.tar.gz` selama 14 hari di `~/e-modelping/cadangan/` (mendukung `mysqldump` MariaDB). Hostinger juga membuat cadangan otomatis (hPanel → **File → Cadangan**). Unduh cadangan secara berkala ke komputer Anda.

## 10. Verifikasi

```bash
curl -sI https://domain-anda.com | head -3                   # HTTP/2 200
curl -s https://domain-anda.com/robots.txt                   # Disallow: /admin
curl -s https://domain-anda.com | grep -o '<title>[^<]*'     # judul dari server
cd ~/e-modelping/current && /opt/alt/php84/usr/bin/php artisan about --only=environment
/opt/alt/php84/usr/bin/php artisan ping:periksa-berkas       # berkas media lengkap
curl -sI https://domain-anda.com/build/assets/$(ls public/build/assets | grep -m1 'pdf.worker.*\.mjs') | grep -i content-type   # text/javascript
```

Di browser: beranda (logo, tema), daftar bab + "Lihat subbab", satu bab, **mode buku** PDF, pencarian, formulir kontak, login admin, **Lupa sandi** (surel benar-benar terkirim), unggah gambar kecil. Bagikan tautan ke WhatsApp untuk mengecek gambar pratinjau.

## 11. Memperbarui website

Setelah ada perubahan kode (di-commit):

```powershell
powershell -ExecutionPolicy Bypass -File scripts\paket-hostinger.ps1
scp -P 65002 paket\e-model-ping-hostinger-YYYYMMDD-HHMMSS.zip u123456789@IP-SERVER:~/
```

```bash
APP=~/e-modelping DOMAIN=domain-anda.com PHP=/opt/alt/php84/usr/bin/php bash ~/e-modelping/current/deploy/hostinger/pasang-rilis.sh ~/e-model-ping-hostinger-YYYYMMDD-HHMMSS.zip
rm ~/e-model-ping-hostinger-*.zip
```

Pemasang otomatis mencadangkan database sebelum migrasi, lalu memindahkan `current` ke rilis baru dan menyimpan 3 rilis terakhir. Perubahan bisa butuh 1–2 menit untuk tampil.

**Kembali ke rilis sebelumnya:**

```bash
cd ~/e-modelping
ls -1t releases | head -3
ln -sfn releases/<RILIS-LAMA> current.baru && mv -Tf current.baru current
```

## 12. Pemecahan masalah

| Gejala                                            | Penyebab & solusi                                                                                                                                                                                                                               |
| ------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Pemasang: `PHP CLI … terlalu lama`                | `php` CLI Hostinger bisa tetap 8.3: jalankan dengan `PHP=/opt/alt/php84/usr/bin/php` (§6).                                                                                                                                                      |
| Pemasang: `Ekstensi PHP '…' belum aktif`          | Aktifkan di **Konfigurasi PHP → Ekstensi PHP**.                                                                                                                                                                                                 |
| `SQLSTATE[HY000] [1045] Access denied` / `[2002]` | Periksa `DB_DATABASE`/`DB_USERNAME` (dengan awalan `u123…_`), `DB_PASSWORD` bertanda kutip, `DB_HOST=localhost`; lalu `/opt/alt/php84/usr/bin/php artisan config:cache`.                                                                        |
| Masih tampil halaman bawaan Hostinger / 403       | `public_html` belum symlink ke `~/e-modelping/current/public` (§7); cek `ls -l ~/domains/domain-anda.com`.                                                                                                                                      |
| Mode buku: "Dokumen gagal dimuat dalam mode buku" | Worker PDF.js `.mjs` dikirim sebagai `text/plain`. `public/.htaccess` sudah memuat `AddType text/javascript .mjs`; cek dengan `curl -sI` (§10). Bila baru diperbaiki, muat ulang paksa browser (Ctrl+F5) karena browser menyimpan respons lama. |
| 500 / "file is writable by group"                 | Izin berkas terlalu longgar (777/666 dari ZIP Windows). Pemasang sudah merapikan ke 755/644; untuk rilis lama: `find ~/e-modelping/current/ -type d -exec chmod 755 {} +` lalu `-type f … chmod 644`.                                           |
| **500 Server Error**                              | Lihat `~/e-modelping/shared/storage/logs/laravel-*.log`.                                                                                                                                                                                        |
| Halaman tanpa gaya (CSS/JS 404)                   | Paket dibuat saat `npm run dev` aktif atau build gagal — buat ulang paket (§4).                                                                                                                                                                 |
| Login kembali ke form / sesi hilang               | `SESSION_SECURE_COOKIE=true` tetapi situs dibuka lewat `http://` — aktifkan SSL & paksa HTTPS (§3.6), pastikan `APP_URL` https.                                                                                                                 |
| Surel "Lupa sandi" tidak sampai                   | `MAIL_*` salah; `MAIL_FROM_ADDRESS` harus sama dengan `MAIL_USERNAME`; port 465 + `MAIL_SCHEME=smtps`.                                                                                                                                          |
| Unggah PDF/gambar gagal (413/422)                 | Naikkan `upload_max_filesize`/`post_max_size` di **Opsi PHP** (§3.2).                                                                                                                                                                           |
| Perubahan pengaturan `.env` tidak berlaku         | Jalankan `/opt/alt/php84/usr/bin/php artisan config:cache` di folder `current` setelah mengubah `.env`.                                                                                                                                         |

**Nanti ingin SSR penuh atau lalu lintas tinggi?** Pindah ke VPS: buat cadangan data dengan `deploy/cadangan.sh` atau `scripts/ekspor-data-lokal.ps1` (dari salinan lokal), lalu ikuti [docs/deploy.md §13](deploy.md#13-memindahkan-data-lokal-opsional).
