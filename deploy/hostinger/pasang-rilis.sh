#!/usr/bin/env bash
# Pasang/perbarui E-Model PING di shared hosting (mis. Hostinger Premium) dari paket ZIP
# buatan scripts/paket-hostinger.ps1. Tanpa sudo, Node, Composer, maupun git di server.
# Panduan lengkap: docs/deploy-hostinger.md
#
# Pemakaian (lewat SSH; di Hostinger `php` CLI bisa 8.3, jadi tunjuk PHP 8.4):
#   APP=~/e-modelping DOMAIN=domain-anda.com PHP=/opt/alt/php84/usr/bin/php \
#     bash pasang-rilis.sh ~/e-model-ping-hostinger-YYYYMMDD-HHMMSS.zip
# Variabel:
#   APP           folder aplikasi (wajib), di luar ~/domains (Hostinger menandai ~/domains/<domain>/ DO_NOT_UPLOAD_HERE)
#   PHP           biner PHP ≥ 8.4.1 (bawaan: php)    DOMAIN        nama domain (untuk petunjuk public_html)
#   SIMPAN_RILIS  jumlah rilis disimpan (bawaan 3)   LEWATI_SEED=1 lewati db:seed (mis. sebelum impor data lokal)
#
# Tata letak (sama dengan VPS, lihat docs/deploy.md):
#   $APP/current -> releases/<waktu>   $APP/shared/.env   $APP/shared/storage   $APP/cadangan
#   public_html  -> $APP/current/public  (symlink, dibuat sekali; lihat panduan)
set -euo pipefail

PAKET=${1:?Pemakaian: APP=<folder-aplikasi> bash pasang-rilis.sh <paket.zip>}
APP=${APP:?Isi APP, mis. APP=~/e-modelping}
PHP=${PHP:-php}
SIMPAN_RILIS=${SIMPAN_RILIS:-3}
APP=$(mkdir -p "$APP" && cd "$APP" && pwd)
REL=$APP/releases/$(date +%Y%m%d%H%M%S)
SHARED=$APP/shared
artisan() { (cd "$REL" && "$PHP" artisan "$@"); }

gagal() { echo "[GAGAL] $*" >&2; exit 1; }

echo "==> 0. Pemeriksaan"
[[ -f $PAKET ]] || gagal "Paket tidak ditemukan: $PAKET"
command -v unzip >/dev/null || gagal "Perintah unzip tidak tersedia."
"$PHP" -r 'exit(version_compare(PHP_VERSION, "8.4.1", ">=") ? 0 : 1);' \
    || gagal "PHP CLI $("$PHP" -r 'echo PHP_VERSION;') terlalu lama (butuh ≥ 8.4.1). Pilih PHP 8.4/8.5 di hPanel, atau jalankan dengan PHP=/jalur/php8.4 (lihat docs/deploy-hostinger.md)."
for eks in pdo_mysql mbstring gd intl zip bcmath fileinfo openssl ctype dom; do
    "$PHP" -r "exit(extension_loaded('$eks') ? 0 : 1);" || gagal "Ekstensi PHP '$eks' belum aktif (hPanel → Konfigurasi PHP → Ekstensi PHP)."
done

echo "==> 1. Folder bersama (sekali)"
mkdir -p "$APP/releases" "$APP/cadangan" \
    "$SHARED/storage/app/private/media" "$SHARED/storage/app/public" \
    "$SHARED/storage/framework/cache/data" "$SHARED/storage/framework/sessions" \
    "$SHARED/storage/framework/testing" "$SHARED/storage/framework/views" "$SHARED/storage/logs"
chmod 700 "$APP/cadangan"

echo "==> 2. Ekstrak paket ke $REL"
SEMENTARA=$(mktemp -d)
trap 'rm -rf "$SEMENTARA"' EXIT
unzip -q "$PAKET" -d "$SEMENTARA"
[[ -f $SEMENTARA/e-model-ping/artisan ]] || gagal "Isi paket tidak dikenali (folder e-model-ping/artisan tidak ada)."
mv "$SEMENTARA/e-model-ping" "$REL"
# ZIP buatan Windows tidak membawa izin Unix (hasil ekstrak 777/666). Rapikan: folder 755, berkas 644.
find "$REL" -type d -exec chmod 755 {} +
find "$REL" -type f -exec chmod 644 {} +
[[ -f $REL/VERSI.txt ]] && sed 's/^/    /' "$REL/VERSI.txt"

if [[ ! -f $SHARED/.env ]]; then
    cp "$REL/deploy/hostinger/env-hostinger.contoh" "$SHARED/.env"
    chmod 600 "$SHARED/.env"
    KUNCI="base64:$("$PHP" -r 'echo base64_encode(random_bytes(32));')"
    sed -i "s|^APP_KEY=.*|APP_KEY=${KUNCI}|" "$SHARED/.env"
    rm -rf "$REL"
    echo ""
    echo "Berkas $SHARED/.env dibuat dari templat (APP_KEY sudah diisi)."
    echo "Lengkapi APP_URL, DB_*, MAIL_*, ADMIN_AWAL_* (nano $SHARED/.env), lalu jalankan perintah ini lagi."
    exit 2
fi
grep -q '^APP_KEY=base64:' "$SHARED/.env" || gagal "APP_KEY di $SHARED/.env kosong."

ln -s "$SHARED/.env" "$REL/.env"
rm -rf "$REL/storage" && ln -s "$SHARED/storage" "$REL/storage"

echo "==> 3. Cadangan sebelum migrasi"
if [[ -L $APP/current ]] && command -v mysqldump >/dev/null; then
    APP="$APP" ENV_FILE="$SHARED/.env" bash "$REL/deploy/cadangan.sh"
else
    echo "    (dilewati: instalasi pertama atau mysqldump tidak tersedia)"
fi

echo "==> 4. Migrasi & data dasar"
artisan migrate --force
if [[ ${LEWATI_SEED:-0} != 1 ]]; then
    artisan db:seed --force
fi

echo "==> 5. Cache produksi"
artisan optimize:clear >/dev/null
artisan config:cache
artisan route:cache
artisan view:cache
artisan event:cache

echo "==> 6. Aktifkan rilis"
ln -sfn "$REL" "$APP/current.baru" && mv -Tf "$APP/current.baru" "$APP/current"

echo "==> 7. Bersihkan rilis lama (simpan $SIMPAN_RILIS)"
ls -1dt "$APP"/releases/*/ | tail -n +$((SIMPAN_RILIS + 1)) | xargs -r rm -rf

echo ""
echo "Rilis aktif: $REL"
# Petunjuk symlink public_html bila belum ada. DOMAIN (opsional) = nama domain di ~/domains/.
if [[ -n ${DOMAIN:-} ]]; then
    PUBLIK="$HOME/domains/$DOMAIN/public_html"
    if [[ ! -L $PUBLIK ]]; then
        echo "LANGKAH SEKALI: arahkan public_html ke aplikasi (docs/deploy-hostinger.md §7):"
        echo "  cd $HOME/domains/$DOMAIN && mv public_html public_html.lama && ln -s $APP/current/public public_html"
    fi
else
    echo "Belum diarahkan? Lihat docs/deploy-hostinger.md §7: public_html -> $APP/current/public (symlink)."
fi
echo "Perubahan bisa butuh 1–2 menit untuk tampil (cache jalur PHP)."
