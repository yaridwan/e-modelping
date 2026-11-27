#!/usr/bin/env bash
# Impor data lokal (arsip dari scripts/ekspor-data-lokal.ps1) ke server produksi E-Model PING.
#
# Yang dilakukan:
#   1. memeriksa arsip, 2. mencadangkan database & media server (deploy/cadangan.sh),
#   3. mode pemeliharaan, 4. MENGGANTI seluruh isi database server dengan database.sql dari arsip,
#   5. menyalin berkas media, 6. migrasi baru + menghapus data demo, 7. membangun ulang cache.
#
# Pemakaian (sebagai www-data, BUKAN root, agar izin berkas tetap benar):
#   sudo -u www-data bash /var/www/e-model-ping/current/deploy/impor-data.sh /tmp/e-model-ping-data-YYYYMMDD-HHMMSS.tar.gz
#   Tambahkan --pertahankan-demo bila data demo ingin tetap ada (tidak disarankan di produksi).
# Setelah selesai: periksa website, lalu hapus arsip (berisi data pribadi).
set -euo pipefail
DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
# shellcheck source=lib/db-klien.sh
source "$DIR/lib/db-klien.sh"

APP=${APP:-/var/www/e-model-ping}
CUR=$APP/current
ENV_FILE=${ENV_FILE:-$APP/shared/.env}
MEDIA=$APP/shared/storage/app/private/media
ARSIP=${1:?Pemakaian: impor-data.sh <arsip.tar.gz> [--pertahankan-demo]}
OPSI=${2:-}

if [[ $EUID -eq 0 ]]; then
    echo "[GAGAL] Jangan jalankan sebagai root. Gunakan: sudo -u www-data bash $0 $ARSIP" >&2
    exit 1
fi
[[ -f $ARSIP ]] || { echo "[GAGAL] Arsip tidak ditemukan/terbaca: $ARSIP" >&2; exit 1; }
[[ -f $ENV_FILE ]] || { echo "[GAGAL] .env tidak ditemukan: $ENV_FILE" >&2; exit 1; }

KERJA=$(mktemp -d)
CNF=""
DIMATIKAN=0
bersihkan() { rm -rf "$KERJA"; [[ -n $CNF ]] && rm -f "$CNF"; return 0; }
gagal() {
    echo "" >&2
    echo "[GAGAL] Impor berhenti di baris $1." >&2
    if [[ $DIMATIKAN -eq 1 ]]; then
        echo "Website TETAP dalam mode pemeliharaan. Pulihkan dari cadangan terbaru di $APP/cadangan (lihat deploy/cadangan.sh), lalu: php artisan up" >&2
    fi
}
trap bersihkan EXIT
trap 'gagal $LINENO' ERR

echo "1/7 Memeriksa arsip ..."
tar -xzf "$ARSIP" -C "$KERJA"
[[ -s $KERJA/database.sql ]] || { echo "[GAGAL] database.sql tidak ada/kosong di arsip." >&2; exit 1; }
grep -q "CREATE TABLE \`riwayat_migrasi\`" "$KERJA/database.sql" || { echo "[GAGAL] database.sql bukan ekspor E-Model PING (tabel riwayat_migrasi tidak ada)." >&2; exit 1; }
[[ -f $KERJA/INFO.txt ]] && sed 's/^/    /' "$KERJA/INFO.txt"

echo "2/7 Mencadangkan data server saat ini ..."
bash "$DIR/cadangan.sh"

cd "$CUR"
echo "3/7 Mode pemeliharaan ..."
php artisan down --retry=60
DIMATIKAN=1

echo "4/7 Mengimpor database (mengganti isi lama) ..."
CNF=$(buat_klien_cnf "$ENV_FILE")
mysql --defaults-extra-file="$CNF" "$(nilai_env DB_DATABASE "$ENV_FILE")" < "$KERJA/database.sql"

echo "5/7 Menyalin berkas media ..."
mkdir -p "$MEDIA"
if [[ -d $KERJA/media ]]; then
    cp -R "$KERJA/media/." "$MEDIA/"
fi

echo "6/7 Migrasi & data demo ..."
php artisan migrate --force
if [[ $OPSI != "--pertahankan-demo" ]]; then
    php artisan ping:hapus-demo --force
fi

echo "7/7 Membangun ulang cache ..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
DIMATIKAN=0

echo ""
echo "Selesai. Periksa website & login admin, lalu hapus arsip: rm '$ARSIP'"
echo "Catatan: bila APP_KEY server berbeda dari lokal, sesi lama & token reset sandi tidak berlaku (normal)."
