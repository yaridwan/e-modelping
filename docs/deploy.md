# Panduan Deploy E-Model PING ke VPS

Panduan langkah demi langkah dari server kosong sampai website online dengan HTTPS, server SSR, cadangan harian, dan data lokal yang sudah dipindahkan. Contoh memakai **Ubuntu 24.04 LTS**; distro lain dapat menyesuaikan nama paket.

> Ringkasan singkat dan daftar periksa ada di [README §15](../README.md#15-deploy-vps-nginx--php-fpm--mysql--node-ssr--https). Dokumen ini adalah versi lengkapnya.

## Daftar isi

1. [Gambaran produksi](#1-gambaran-produksi)
2. [Kebutuhan](#2-kebutuhan)
3. [Siapkan server](#3-siapkan-server)
4. [Pasang perangkat lunak](#4-pasang-perangkat-lunak)
5. [Database MySQL](#5-database-mysql)
6. [Folder & izin](#6-folder--izin)
7. [Berkas `.env` produksi](#7-berkas-env-produksi)
8. [Rilis pertama](#8-rilis-pertama)
9. [Akun admin](#9-akun-admin)
10. [Nginx & HTTPS](#10-nginx--https)
11. [Layanan SSR](#11-layanan-ssr)
12. [Cron, retensi & cadangan](#12-cron-retensi--cadangan)
13. [Memindahkan data lokal (opsional)](#13-memindahkan-data-lokal-opsional)
14. [Verifikasi](#14-verifikasi)
15. [Rilis berikutnya, rollback, pemulihan](#15-rilis-berikutnya-rollback-pemulihan)
16. [Perawatan rutin](#16-perawatan-rutin)
17. [Pemecahan masalah](#17-pemecahan-masalah)
18. [Bagaimana dengan shared hosting?](#18-bagaimana-dengan-shared-hosting)

---

## 1. Gambaran produksi

```text
Pengunjung ──HTTPS──▶ Nginx (443) ──▶ PHP-FPM 8.4 (Laravel 13) ──▶ MySQL 8.4 (127.0.0.1:3306)
                         │                    │
                         │                    └──HTTP lokal──▶ Server SSR Inertia (Node 22, 127.0.0.1:13714)
                         └── /build/* (aset Vite berhash, cache 1 tahun)

cron (www-data): schedule:run tiap menit (retensi 02:30 WIB) · cadangan harian 01:30
```

Struktur folder di server:

```text
/var/www/e-model-ping/
├── current -> releases/20261127120000   # symlink rilis aktif (document root: current/public)
├── releases/                            # 5 rilis terakhir (deploy.sh)
├── shared/
│   ├── .env                             # rahasia produksi (dibagi semua rilis)
│   └── storage/                         # unggahan, log, cache, sesi (dibagi semua rilis)
└── cadangan/                            # db-*.sql.gz + media-*.tar.gz (14 hari)
```

- Antrean memakai driver `sync` → **tidak perlu** queue worker/Redis.
- Sesi & cache berbasis berkas → tidak perlu tabel `sessions`/`cache`.
- Satu-satunya proses tambahan adalah **server SSR Node** (systemd).

## 2. Kebutuhan

| Kebutuhan | Rekomendasi                                                                                |
| --------- | ------------------------------------------------------------------------------------------ |
| VPS       | 1 vCPU, **2 GB RAM** (1 GB bisa dengan swap), 20 GB SSD, Ubuntu 24.04 LTS, akses root/sudo |
| Domain    | Domain/subdomain dengan record **A** (dan **AAAA** bila ada IPv6) mengarah ke IP VPS       |
| Surel     | Akun SMTP (layanan surel transaksional atau surel domain) untuk fitur "Lupa sandi"         |
| Lokal     | Git, akses SSH ke VPS; untuk memindahkan data: proyek lokal + MySQL lokal berjalan         |

Versi yang diuji: PHP 8.5 (minimum **8.4.1**), MySQL **8.4**, Node **22+** (lokal: 24). MySQL 8.0 kemungkinan berjalan (CHECK constraint & kolom generated didukung) tetapi **tidak diuji**; MariaDB tidak didukung.

## 3. Siapkan server

Masuk sebagai root (atau pengguna sudo), lalu:

```bash
# Pembaruan sistem & zona waktu
sudo apt update && sudo apt -y upgrade
sudo timedatectl set-timezone Asia/Jakarta

# Pengguna deploy (pemilik kode), anggota grup www-data
sudo adduser --disabled-password --gecos "" deploy
sudo usermod -aG www-data,sudo deploy
sudo passwd deploy                      # sandi untuk sudo
sudo rsync -a ~/.ssh/ /home/deploy/.ssh/ && sudo chown -R deploy:deploy /home/deploy/.ssh   # salin kunci SSH Anda

# Firewall: hanya SSH + HTTP/HTTPS
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'             # jalankan setelah Nginx terpasang bila profil belum ada
sudo ufw enable

# Swap 2 GB (disarankan untuk RAM ≤ 2 GB; build npm butuh memori)
sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

Setelah ini, login sebagai `deploy` (`ssh deploy@IP-SERVER`).

## 4. Pasang perangkat lunak

### PHP 8.4 + ekstensi

```bash
sudo apt -y install software-properties-common ca-certificates curl unzip git
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt -y install php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml \
  php8.4-gd php8.4-intl php8.4-zip php8.4-bcmath php8.4-curl php8.4-opcache
php -v    # harus 8.4.1 atau lebih baru
```

Ekstensi yang dibutuhkan: `pdo_mysql, mbstring, openssl, fileinfo, gd (JPEG/PNG/WebP), intl, zip, bcmath, ctype, dom, xml`.

Pasang pengaturan PHP proyek (batas unggah 30 MB, OPcache, zona waktu) setelah kode ada di server ([§8](#8-rilis-pertama)) — atau salin langsung dari repo:

```bash
curl -fsSL https://raw.githubusercontent.com/yaridwan/e-modelping/main/deploy/php/99-e-model-ping.ini \
  | sudo tee /etc/php/8.4/fpm/conf.d/99-e-model-ping.ini /etc/php/8.4/cli/conf.d/99-e-model-ping.ini >/dev/null
sudo systemctl reload php8.4-fpm
```

### Composer 2

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
HASH=$(curl -sS https://composer.github.io/installer.sig)
php -r "if (hash_file('sha384', 'composer-setup.php') === '$HASH') { echo 'Installer OK'; } else { echo 'Installer RUSAK'; unlink('composer-setup.php'); exit(1); } echo PHP_EOL;"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php && composer --version
```

### MySQL 8.4 LTS

Repositori bawaan Ubuntu 24.04 berisi MySQL 8.0. Untuk 8.4 LTS pakai repositori resmi MySQL:

```bash
cd /tmp
# Ambil paket konfigurasi terbaru dari https://dev.mysql.com/downloads/repo/apt/
wget https://dev.mysql.com/get/mysql-apt-config_0.8.33-1_all.deb   # sesuaikan nomor versi terbaru
sudo dpkg -i mysql-apt-config_*_all.deb    # pilih "mysql-8.4-lts" lalu OK
sudo apt update && sudo apt -y install mysql-server
sudo systemctl enable --now mysql
mysql --version                            # 8.4.x
sudo mysql_secure_installation             # sandi root kuat, hapus user anonim & DB test
```

### Node.js 22

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt -y install nodejs
node -v && npm -v                          # v22.x
```

### Nginx & Certbot

```bash
sudo apt -y install nginx certbot python3-certbot-nginx
sudo systemctl enable --now nginx
```

## 5. Database MySQL

```bash
sudo mysql
```

```sql
CREATE DATABASE e_model_ping CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'e_model_ping'@'127.0.0.1' IDENTIFIED BY 'GANTI-DENGAN-SANDI-KUAT';
CREATE USER 'e_model_ping'@'localhost' IDENTIFIED BY 'GANTI-DENGAN-SANDI-KUAT';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES, LOCK TABLES
  ON e_model_ping.* TO 'e_model_ping'@'127.0.0.1', 'e_model_ping'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Buat sandi acak, mis. `openssl rand -base64 24`. Sandi hanya disimpan di `shared/.env`.

## 6. Folder & izin

```bash
sudo mkdir -p /var/www/e-model-ping/{releases,shared/storage}
sudo chown -R deploy:www-data /var/www/e-model-ping
sudo chmod 2775 /var/www/e-model-ping /var/www/e-model-ping/releases /var/www/e-model-ping/shared

# Kerangka storage Laravel, milik www-data (pengguna PHP-FPM)
cd /var/www/e-model-ping/shared/storage
sudo mkdir -p app/private/media app/public framework/{cache/data,sessions,testing,views} logs
sudo chown -R www-data:www-data /var/www/e-model-ping/shared/storage
sudo chmod -R ug+rwX /var/www/e-model-ping/shared/storage
sudo find /var/www/e-model-ping/shared/storage -type d -exec chmod g+s {} +

# Folder cadangan (hanya www-data)
sudo install -d -o www-data -g www-data -m 700 /var/www/e-model-ping/cadangan
```

Aturan izin: kode milik `deploy` (dibaca www-data lewat grup), yang ditulis aplikasi hanya `shared/storage` dan `bootstrap/cache` tiap rilis. **Jangan pernah** `chmod 777`.

## 7. Berkas `.env` produksi

```bash
nano /var/www/e-model-ping/shared/.env
```

```dotenv
APP_NAME="E-Model PING"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://domain-anda.id

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DAILY_DAYS=14
LOG_LEVEL=warning
LOG_DEPRECATIONS_CHANNEL=null

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=e_model_ping
DB_USERNAME=e_model_ping
DB_PASSWORD="GANTI-DENGAN-SANDI-KUAT"

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.penyedia-surel.id
MAIL_PORT=587
MAIL_USERNAME=akun-smtp
MAIL_PASSWORD="sandi-smtp"
MAIL_FROM_ADDRESS="noreply@domain-anda.id"
MAIL_FROM_NAME="${APP_NAME}"

INERTIA_SSR_ENABLED=true
INERTIA_SSR_URL=http://127.0.0.1:13714

# Hanya untuk membuat akun admin pertama (§9) — HAPUS sandi setelah akun dibuat.
ADMIN_AWAL_SUREL=surel-asli-anda@domain.id
ADMIN_AWAL_NAMA="Nama Anda"
ADMIN_AWAL_SANDI="sandi-awal-min-12-karakter"

PING_BATAS_UNGGAH_MAKS_MB=25
# Isi bila di belakang proxy/CDN tepercaya (mis. Cloudflare: daftar IP Cloudflare, atau 127.0.0.1 untuk proxy lokal).
TRUSTED_PROXIES=

VITE_APP_NAME="${APP_NAME}"
```

```bash
sudo chown deploy:www-data /var/www/e-model-ping/shared/.env
sudo chmod 640 /var/www/e-model-ping/shared/.env
```

Isi `APP_KEY` **sekarang** (sekali saja, jangan pernah diganti setelah website berjalan):

```bash
KUNCI="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
sed -i "s|^APP_KEY=.*|APP_KEY=${KUNCI}|" /var/www/e-model-ping/shared/.env
grep -c '^APP_KEY=base64:' /var/www/e-model-ping/shared/.env    # 1
```

**Simpan salinan `.env` di tempat aman di luar server** — tanpa `APP_KEY` yang sama, cadangan tidak dapat dipulihkan dengan utuh.

## 8. Rilis pertama

Sebagai pengguna `deploy`:

```bash
# Ambil skrip rilis dari repo (atau clone repo sekali untuk membacanya)
curl -fsSL https://raw.githubusercontent.com/yaridwan/e-modelping/main/deploy/deploy.sh -o ~/deploy.sh
```

Rilis pertama butuh layanan SSR dan izin sudo yang belum ada, jadi jalankan langkah persiapan berikut sekali:

```bash
# 1) Pasang layanan SSR (dipakai deploy.sh) — langsung dari repo
curl -fsSL https://raw.githubusercontent.com/yaridwan/e-modelping/main/deploy/systemd/e-model-ping-ssr.service \
  | sudo tee /etc/systemd/system/e-model-ping-ssr.service >/dev/null
sudo systemctl daemon-reload && sudo systemctl enable e-model-ping-ssr

# 2) Izinkan deploy menjalankan perintah sebagai www-data & me-restart layanan tanpa sandi (opsional tapi praktis)
echo 'deploy ALL=(www-data) NOPASSWD: ALL
deploy ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.4-fpm, /usr/bin/systemctl restart e-model-ping-ssr' \
  | sudo tee /etc/sudoers.d/e-model-ping && sudo chmod 440 /etc/sudoers.d/e-model-ping && sudo visudo -c

# 3) Rilis
bash ~/deploy.sh
```

Bila sebuah langkah gagal, perbaiki penyebabnya (pesan galat ditampilkan) lalu jalankan `bash ~/deploy.sh` lagi — setiap rilis memakai folder baru, dan symlink `current` baru dipindah setelah semua langkah berhasil.

Yang dilakukan `deploy.sh` setiap rilis: clone `main` → symlink `.env` & `storage` → `composer install --no-dev` → `npm ci && npm run build:ssr` → **cadangan** → `migrate --force` → `db:seed --force` (data dasar, tidak menimpa konten) → `config/route/view/event:cache` → ganti symlink `current` → reload PHP-FPM → restart SSR → `inertia:check-ssr` → hapus rilis lama (simpan 5).

Pasang juga pengaturan PHP proyek bila belum (lihat [§4](#php-84--ekstensi)).

## 9. Akun admin

`db:seed` pada rilis pertama membuat akun admin dari `ADMIN_AWAL_*` bila belum ada akun. Bila tidak, jalankan:

```bash
cd /var/www/e-model-ping/current && sudo -u www-data php artisan ping:admin-awal
```

Lalu **hapus nilai `ADMIN_AWAL_SANDI`** dari `shared/.env` dan bangun ulang cache:

```bash
sudo -u www-data php artisan config:cache
```

Login di `https://domain-anda.id/admin/masuk` (surel + sandi + soal hitungan), lalu:

- ganti sandi di **Keamanan**,
- pastikan surel di **Profil Saya** adalah surel asli (untuk "Lupa sandi"),
- isi **Pengaturan** (identitas, logo, kontak, SEO).

## 10. Nginx & HTTPS

```bash
sudo cp /var/www/e-model-ping/current/deploy/nginx/e-model-ping.conf /etc/nginx/sites-available/e-model-ping
sudo sed -i 's/emodelping\.example/domain-anda.id/g' /etc/nginx/sites-available/e-model-ping
```

Konfigurasi itu merujuk sertifikat yang belum ada, jadi terbitkan sertifikat dulu lewat situs bawaan Nginx (port 80, folder `/var/www/html`), baru aktifkan konfigurasi proyek:

```bash
# 1) Sertifikat (situs default Nginx masih aktif dan melayani tantangan ACME)
sudo certbot certonly --webroot -w /var/www/html -d domain-anda.id -d www.domain-anda.id \
  --agree-tos -m surel-anda@domain.id --no-eff-email

# 2) Ganti situs default dengan konfigurasi proyek
sudo rm -f /etc/nginx/sites-enabled/default
sudo ln -s /etc/nginx/sites-available/e-model-ping /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# 3) Perpanjangan otomatis: ubah metode ke nginx agar tidak bergantung /var/www/html, lalu uji
sudo certbot reconfigure --cert-name domain-anda.id --nginx
sudo certbot renew --dry-run
```

Konfigurasi contoh ([deploy/nginx/e-model-ping.conf](../deploy/nginx/e-model-ping.conf)): HTTP→HTTPS 301, HTTP/2, document root hanya `current/public`, `client_max_body_size 30M`, cache 1 tahun untuk `/build/`, berkas tersembunyi ditolak, `fastcgi_pass unix:/run/php/php8.4-fpm.sock`. Header keamanan & CSP (dengan nonce) dikirim oleh aplikasi.

## 11. Layanan SSR

Server SSR merender HTML awal (SEO, pembaca tanpa JavaScript). Hanya mendengar `127.0.0.1:13714` — **jangan** dibuka di firewall.

```bash
sudo systemctl status e-model-ping-ssr
cd /var/www/e-model-ping/current && sudo -u www-data php artisan inertia:check-ssr   # "Inertia SSR server is running."
journalctl -u e-model-ping-ssr -n 50 --no-pager                                   # log
```

Alternatif Supervisor: [deploy/supervisor/e-model-ping-ssr.conf](../deploy/supervisor/e-model-ping-ssr.conf) (pilih salah satu, jangan keduanya).

## 12. Cron, retensi & cadangan

```bash
sudo cp /var/www/e-model-ping/current/deploy/cron/e-model-ping /etc/cron.d/e-model-ping
sudo chmod 644 /etc/cron.d/e-model-ping
```

Isi cron:

- `schedule:run` tiap menit → `ping:bersihkan-retensi` harian 02:30 WIB (statistik & pesan lewat masa retensi).
- `deploy/cadangan.sh` pukul 01:30 → `cadangan/db-*.sql.gz` + `cadangan/media-*.tar.gz`, simpan 14 hari (`SIMPAN_HARI`).

Uji cadangan sekali secara manual:

```bash
sudo -u www-data bash /var/www/e-model-ping/current/deploy/cadangan.sh
ls -lh /var/www/e-model-ping/cadangan
```

**Salin cadangan ke luar server** secara berkala (mis. `rsync`/`scp` ke komputer Anda atau penyimpanan awan) — cadangan di server yang sama tidak melindungi dari server rusak/hilang.

## 13. Memindahkan data lokal (opsional)

Bila Anda sudah mengisi konten di komputer lokal (logo, sampul, galeri, pengaturan, suntingan bab, pesan), pindahkan database + media sekaligus:

```powershell
# Komputer lokal (Windows, MySQL lokal berjalan)
powershell -ExecutionPolicy Bypass -File scripts\ekspor-data-lokal.ps1
scp cadangan\e-model-ping-data-YYYYMMDD-HHMMSS.tar.gz deploy@IP-SERVER:/tmp/
```

```bash
# Server
sudo chgrp www-data /tmp/e-model-ping-data-*.tar.gz && sudo chmod 640 /tmp/e-model-ping-data-*.tar.gz
sudo -u www-data bash /var/www/e-model-ping/current/deploy/impor-data.sh /tmp/e-model-ping-data-YYYYMMDD-HHMMSS.tar.gz
rm /tmp/e-model-ping-data-*.tar.gz       # arsip berisi data pribadi
```

`impor-data.sh`: cadangkan server → mode pemeliharaan → **ganti** isi database → salin media → migrasi → hapus data demo (`ping:hapus-demo`) → bangun ulang cache → online. Bila gagal, website tetap dalam mode pemeliharaan dan cadangan ada di `/var/www/e-model-ping/cadangan`.

Catatan: akun admin ikut dari database lokal — login memakai surel & sandi lokal Anda, lalu ganti sandinya. Bila `APP_KEY` server berbeda dari lokal, sesi lama dan token reset sandi tidak berlaku (normal).

## 14. Verifikasi

```bash
curl -sI https://domain-anda.id | head -5                       # 200, header keamanan
curl -s https://domain-anda.id | grep -c '<h1'                  # 1 (HTML hasil SSR)
curl -s https://domain-anda.id/robots.txt                       # produksi: Disallow: /admin
curl -s https://domain-anda.id/sitemap.xml | head -5
curl -sI http://domain-anda.id | grep -i location               # 301 ke https
cd /var/www/e-model-ping/current && sudo -u www-data php artisan about --only=environment
sudo -u www-data php artisan ping:periksa-berkas                # berkas media lengkap
```

Lalu di browser: beranda (logo, tema terang/gelap), satu bab, **mode buku** PDF, pencarian, formulir kontak, login admin, **Lupa sandi** (surel benar-benar terkirim), unggah gambar kecil di admin. Bagikan tautan ke WhatsApp untuk memeriksa gambar pratinjau (Open Graph).

## 15. Rilis berikutnya, rollback, pemulihan

**Rilis baru** (setelah `git push` ke `main`):

```bash
bash ~/deploy.sh        # atau curl ulang deploy.sh bila skripnya berubah
```

**Rollback kode** ke rilis sebelumnya:

```bash
cd /var/www/e-model-ping
ls -1t releases | head -3                              # pilih rilis sebelumnya
ln -sfn /var/www/e-model-ping/releases/<RILIS-LAMA> current
sudo systemctl reload php8.4-fpm && sudo systemctl restart e-model-ping-ssr
```

Rollback migrasi hanya bila migrasi rilis tersebut reversibel, dan **setelah** cadangan.

**Pulihkan dari cadangan:**

```bash
cd /var/www/e-model-ping/current
sudo -u www-data php artisan down
source deploy/lib/db-klien.sh
CNF=$(buat_klien_cnf /var/www/e-model-ping/shared/.env)
gunzip -c /var/www/e-model-ping/cadangan/db-YYYYMMDD-HHMMSS.sql.gz | mysql --defaults-extra-file="$CNF" e_model_ping
rm -f "$CNF"
sudo -u www-data tar -xzf /var/www/e-model-ping/cadangan/media-YYYYMMDD-HHMMSS.tar.gz -C /var/www/e-model-ping/shared/storage/app/private
sudo -u www-data php artisan optimize:clear && sudo -u www-data php artisan config:cache
sudo -u www-data php artisan up
```

## 16. Perawatan rutin

| Kapan       | Tindakan                                                                                             |
| ----------- | ---------------------------------------------------------------------------------------------------- |
| Mingguan    | Cek `storage/logs/laravel-*.log`, ruang disk (`df -h`), salin cadangan ke luar server                |
| Bulanan     | `sudo apt update && sudo apt upgrade`; `composer audit` & `npm audit` di lokal lalu rilis bila perlu |
| Tiap rilis  | CI GitHub hijau, `deploy.sh`, cek beranda + admin                                                    |
| Per 3 bulan | Uji pemulihan cadangan di database terpisah                                                          |

Jangan pernah menjalankan `migrate:fresh`, `ping:reset-demo`, `DataDemoSeeder`, atau `key:generate` pada instalasi yang sudah berjalan.

## 17. Pemecahan masalah

| Gejala                               | Penyebab & solusi                                                                                                                    |
| ------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------ |
| **502 Bad Gateway**                  | PHP-FPM mati/soket salah: `sudo systemctl status php8.4-fpm`; pastikan `fastcgi_pass` = `/run/php/php8.4-fpm.sock`.                  |
| **500** tanpa detail                 | Lihat `storage/logs/laravel-*.log`; sering karena izin (`§6`) atau `.env` belum terbaca → `php artisan config:cache` ulang.          |
| Halaman tampil tapi HTML awal kosong | SSR mati: `systemctl status e-model-ping-ssr`, `inertia:check-ssr`; `bootstrap/ssr/app.js` ada? (`npm run build:ssr`).               |
| Tampilan tanpa CSS/JS                | Ada `public/hot` tertinggal (dari `npm run dev`) — hapus; atau build gagal saat rilis.                                               |
| **413 Request Entity Too Large**     | Samakan `client_max_body_size` (Nginx), `upload_max_filesize`/`post_max_size` (PHP), `PING_BATAS_UNGGAH_MAKS_MB`.                    |
| Login selalu kembali / sesi hilang   | `SESSION_SECURE_COOKIE=true` tetapi situs diakses lewat HTTP; atau izin `storage/framework/sessions`.                                |
| Tautan/canonical memakai `http://`   | `APP_URL` belum `https://…`; di belakang proxy/CDN isi `TRUSTED_PROXIES`.                                                            |
| Surel "Lupa sandi" tidak sampai      | Periksa `MAIL_*`, port 587/465 tidak diblokir penyedia VPS, `MAIL_FROM_ADDRESS` sesuai domain (SPF/DKIM).                            |
| `Permission denied` di storage       | `sudo chown -R www-data:www-data shared/storage && sudo chmod -R ug+rwX shared/storage`; jalankan artisan dengan `sudo -u www-data`. |
| Composer: `requires php >=8.4.1`     | PHP CLI server terlalu lama → pasang `php8.4-cli` dan `sudo update-alternatives --set php /usr/bin/php8.4`.                          |

## 18. Bagaimana dengan shared hosting?

Website ini membutuhkan **proses Node yang berjalan terus** untuk SSR dan **PHP ≥ 8.4.1**. Kebanyakan shared hosting (cPanel) tidak menyediakan keduanya, sehingga **tidak didukung**. Pilihan murah yang cocok: VPS kecil (1–2 GB RAM) di penyedia lokal maupun global. Tanpa SSR, website masih berjalan (halaman dirender di browser) tetapi SEO dan pembaca tanpa JavaScript dirugikan — tidak disarankan.
