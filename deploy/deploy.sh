#!/usr/bin/env bash
# Rilis E-Model PING tanpa downtime berbasis folder releases/ + symlink current.
# Jalankan sebagai pengguna `deploy` (anggota grup www-data, punya sudo). Panduan lengkap: docs/deploy.md
#
#   bash deploy.sh                       # rilis cabang main dari GitHub
#   BRANCH=main REPO=<url> bash deploy.sh
#
# Prasyarat (sekali): /var/www/e-model-ping/shared/.env dan shared/storage sudah ada (lihat docs/deploy.md §6–§7).
# Artisan dijalankan sebagai www-data agar berkas cache/log di storage tetap milik pengguna web.
set -euo pipefail

APP=${APP:-/var/www/e-model-ping}
REPO=${REPO:-https://github.com/yaridwan/e-modelping.git}
BRANCH=${BRANCH:-main}
PHP_FPM=${PHP_FPM:-php8.4-fpm}
SIMPAN_RILIS=${SIMPAN_RILIS:-5}
REL=$APP/releases/$(date +%Y%m%d%H%M%S)
artisan() { sudo -u www-data php artisan "$@"; }

[[ -f $APP/shared/.env ]] || { echo "[GAGAL] $APP/shared/.env belum ada (docs/deploy.md §7)." >&2; exit 1; }
[[ -d $APP/shared/storage ]] || { echo "[GAGAL] $APP/shared/storage belum ada (docs/deploy.md §6)." >&2; exit 1; }

echo "==> 1. Ambil kode ($BRANCH) ke $REL"
git clone --depth 1 --branch "$BRANCH" "$REPO" "$REL"
ln -s "$APP/shared/.env" "$REL/.env"
rm -rf "$REL/storage" && ln -s "$APP/shared/storage" "$REL/storage"
cd "$REL"

echo "==> 2. Dependensi PHP & build frontend + SSR"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress
npm ci --no-audit --no-fund
npm run build:ssr
rm -rf node_modules/.cache
# www-data perlu menulis cache konfigurasi/rute di rilis ini.
chgrp -R www-data bootstrap/cache && chmod -R g+rwX bootstrap/cache

echo "==> 3. Cadangan sebelum migrasi"
sudo -u www-data env APP="$APP" bash "$REL/deploy/cadangan.sh"

echo "==> 4. Migrasi & data dasar"
artisan migrate --force   # JANGAN pernah migrate:fresh di produksi
artisan db:seed --force   # hanya data dasar; tidak menimpa konten/sandi/branding yang sudah ada

echo "==> 5. Cache produksi"
artisan config:cache
artisan route:cache
artisan view:cache
artisan event:cache

echo "==> 6. Aktifkan rilis"
ln -sfn "$REL" "$APP/current"
sudo systemctl reload "$PHP_FPM"
sudo systemctl restart e-model-ping-ssr
sleep 3
artisan inertia:check-ssr

echo "==> 7. Bersihkan rilis lama (simpan $SIMPAN_RILIS terakhir)"
ls -1dt "$APP"/releases/*/ | tail -n +$((SIMPAN_RILIS + 1)) | xargs -r rm -rf

echo "Rilis selesai: $REL"
