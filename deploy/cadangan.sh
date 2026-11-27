#!/usr/bin/env bash
# Cadangan database + berkas media E-Model PING (di luar folder web). Dijalankan harian lewat cron
# (deploy/cron/e-model-ping) dan otomatis sebelum deploy/impor-data.sh.
#
# Pemakaian:  sudo -u www-data bash /var/www/e-model-ping/current/deploy/cadangan.sh
# Variabel  :  APP (bawaan /var/www/e-model-ping), ENV_FILE (bawaan $APP/shared/.env),
#              TUJUAN (bawaan $APP/cadangan), SIMPAN_HARI (bawaan 14)
#
# Pemulihan (contoh):
#   php artisan down
#   gunzip -c $APP/cadangan/db-YYYYMMDD-HHMMSS.sql.gz | mysql --defaults-extra-file=<cnf> <nama_db>
#   tar -xzf $APP/cadangan/media-YYYYMMDD-HHMMSS.tar.gz -C $APP/shared/storage/app/private
#   php artisan optimize:clear && php artisan config:cache && php artisan up
# Salin cadangan secara berkala ke tempat lain (komputer/penyimpanan awan): cadangan di server yang sama
# tidak melindungi dari kerusakan disk atau server hilang.
set -euo pipefail
DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
# shellcheck source=lib/db-klien.sh
source "$DIR/lib/db-klien.sh"

APP=${APP:-/var/www/e-model-ping}
ENV_FILE=${ENV_FILE:-$APP/shared/.env}
TUJUAN=${TUJUAN:-$APP/cadangan}
SIMPAN_HARI=${SIMPAN_HARI:-14}
STEMPEL=$(date +%Y%m%d-%H%M%S)

[[ -f $ENV_FILE ]] || { echo "[GAGAL] .env tidak ditemukan: $ENV_FILE" >&2; exit 1; }
umask 077
mkdir -p "$TUJUAN"

CNF=$(buat_klien_cnf "$ENV_FILE")
trap 'rm -f "$CNF"' EXIT
DB=$(nilai_env DB_DATABASE "$ENV_FILE")

mysqldump --defaults-extra-file="$CNF" --single-transaction --quick --no-tablespaces --hex-blob \
    --set-gtid-purged=OFF "$DB" | gzip -9 > "$TUJUAN/db-$STEMPEL.sql.gz"

MEDIA_INDUK=$APP/shared/storage/app/private
if [[ -d $MEDIA_INDUK/media ]]; then
    tar -czf "$TUJUAN/media-$STEMPEL.tar.gz" -C "$MEDIA_INDUK" media
else
    echo "Folder media belum ada ($MEDIA_INDUK/media), dilewati."
fi

# Hapus cadangan yang lebih tua dari SIMPAN_HARI hari.
find "$TUJUAN" -maxdepth 1 -type f \( -name 'db-*.sql.gz' -o -name 'media-*.tar.gz' \) -mtime +"$SIMPAN_HARI" -delete

echo "[$(date '+%F %T')] Cadangan tersimpan: $TUJUAN/db-$STEMPEL.sql.gz$([[ -f $TUJUAN/media-$STEMPEL.tar.gz ]] && echo " dan media-$STEMPEL.tar.gz")"
