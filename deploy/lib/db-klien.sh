# Fungsi bersama skrip deploy E-Model PING (di-source, bukan dijalankan langsung).
# Membaca kredensial database dari .env Laravel dan membuat berkas opsi klien MySQL sementara (izin 600),
# sehingga sandi tidak muncul di argumen proses (ps) maupun riwayat shell.

# nilai_env KUNCI BERKAS_ENV  -> mencetak nilai (tanda kutip pembungkus dibuang)
nilai_env() {
    local baris v
    baris=$(grep -E "^$1=" "$2" | tail -n 1 || true)
    v=${baris#*=}
    v=${v%$'\r'}
    if [[ ${#v} -ge 2 && ( ( $v == \"*\" ) || ( $v == \'*\' ) ) ]]; then
        v=${v:1:${#v}-2}
    fi
    printf '%s' "$v"
}

# buat_klien_cnf BERKAS_ENV  -> mencetak jalur berkas opsi sementara; hapus sendiri setelah dipakai.
buat_klien_cnf() {
    local env=$1 cnf sandi
    cnf=$(mktemp)
    chmod 600 "$cnf"
    sandi=$(nilai_env DB_PASSWORD "$env")
    sandi=${sandi//\\/\\\\}
    sandi=${sandi//\"/\\\"}
    {
        echo "[client]"
        echo "host=$(nilai_env DB_HOST "$env")"
        echo "port=$(nilai_env DB_PORT "$env")"
        echo "user=$(nilai_env DB_USERNAME "$env")"
        echo "password=\"$sandi\""
        echo "default-character-set=utf8mb4"
    } > "$cnf"
    printf '%s' "$cnf"
}
