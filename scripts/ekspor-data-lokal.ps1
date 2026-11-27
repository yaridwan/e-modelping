# Ekspor data lokal E-Model PING (database + berkas media unggahan) menjadi satu arsip .tar.gz
# untuk dipindahkan ke server (lihat deploy/impor-data.sh dan README bagian 15a).
#
# Pemakaian (PowerShell, dari folder proyek):
#   powershell -ExecutionPolicy Bypass -File scripts\ekspor-data-lokal.ps1
#
# Hasil: cadangan\e-model-ping-data-YYYYMMDD-HHMMSS.tar.gz berisi:
#   database.sql  - seluruh tabel (struktur + isi), tanpa CREATE DATABASE
#   media/        - isi storage\app\private\media (logo, sampul, gambar, PDF)
#   INFO.txt      - ringkasan ekspor
# PERHATIAN: arsip berisi data pribadi (pesan kontak, akun admin ber-hash). Simpan aman dan hapus setelah diimpor.
param(
    [string]$MysqlBin = 'C:\mysql84\bin',
    [string]$Tujuan = ''
)
$ErrorActionPreference = 'Stop'
$akar = Split-Path -Parent $PSScriptRoot
$berkasEnv = Join-Path $akar '.env'
if (-not (Test-Path $berkasEnv)) { throw ".env tidak ditemukan di $akar" }

function Get-NilaiEnv([string]$Kunci) {
    $baris = Get-Content $berkasEnv | Where-Object { $_ -match "^$Kunci=" } | Select-Object -Last 1
    if (-not $baris) { return '' }
    $nilai = $baris.Substring($Kunci.Length + 1).Trim()
    if ($nilai.Length -ge 2 -and (($nilai.StartsWith('"') -and $nilai.EndsWith('"')) -or ($nilai.StartsWith("'") -and $nilai.EndsWith("'")))) {
        $nilai = $nilai.Substring(1, $nilai.Length - 2)
    }
    return $nilai
}

$mysqldump = Join-Path $MysqlBin 'mysqldump.exe'
if (-not (Test-Path $mysqldump)) { throw "mysqldump tidak ditemukan: $mysqldump (atur -MysqlBin)" }
$tar = Join-Path $env:SystemRoot 'System32\tar.exe'
if (-not (Test-Path $tar)) { throw 'tar.exe bawaan Windows tidak ditemukan (butuh Windows 10 1803+).' }

$db = Get-NilaiEnv 'DB_DATABASE'
$stempel = Get-Date -Format 'yyyyMMdd-HHmmss'
if (-not $Tujuan) { $Tujuan = Join-Path $akar 'cadangan' }
New-Item -ItemType Directory -Force -Path $Tujuan | Out-Null
$arsip = Join-Path $Tujuan "e-model-ping-data-$stempel.tar.gz"

$kerja = Join-Path $env:TEMP "e-model-ping-ekspor-$stempel"
New-Item -ItemType Directory -Force -Path (Join-Path $kerja 'media') | Out-Null
$cnf = Join-Path $env:TEMP "e-model-ping-klien-$stempel.cnf"

try {
    # Kredensial lewat berkas opsi sementara (tidak tampil di daftar proses).
    $sandi = (Get-NilaiEnv 'DB_PASSWORD').Replace('\', '\\').Replace('"', '\"')
    $isiCnf = "[client]`r`nhost=$(Get-NilaiEnv 'DB_HOST')`r`nport=$(Get-NilaiEnv 'DB_PORT')`r`nuser=$(Get-NilaiEnv 'DB_USERNAME')`r`npassword=`"$sandi`"`r`n"
    [System.IO.File]::WriteAllText($cnf, $isiCnf, (New-Object System.Text.UTF8Encoding($false)))

    Write-Host "1/3 Mengekspor database '$db' ..."
    & $mysqldump "--defaults-extra-file=$cnf" --single-transaction --quick --no-tablespaces --hex-blob `
        --default-character-set=utf8mb4 --set-gtid-purged=OFF "--result-file=$(Join-Path $kerja 'database.sql')" $db
    if ($LASTEXITCODE -ne 0) { throw "mysqldump gagal (kode $LASTEXITCODE). Pastikan MySQL 8.4 berjalan di port $(Get-NilaiEnv 'DB_PORT')." }

    Write-Host '2/3 Menyalin berkas media ...'
    $media = Join-Path $akar 'storage\app\private\media'
    $jumlahBerkas = 0
    if (Test-Path $media) {
        Copy-Item -Path (Join-Path $media '*') -Destination (Join-Path $kerja 'media') -Recurse -Force
        $jumlahBerkas = (Get-ChildItem (Join-Path $kerja 'media') -Recurse -File).Count
    }

    $ukuranSql = [math]::Round((Get-Item (Join-Path $kerja 'database.sql')).Length / 1MB, 2)
    @(
        'Ekspor data E-Model PING',
        "Tanggal       : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss zzz')",
        "Database      : $db ($ukuranSql MB SQL)",
        "Berkas media  : $jumlahBerkas berkas",
        'Impor di server: bash deploy/impor-data.sh <arsip.tar.gz>  (lihat README bagian 15a)',
        'Arsip berisi data pribadi. Hapus setelah diimpor.'
    ) | Set-Content -Path (Join-Path $kerja 'INFO.txt') -Encoding UTF8

    Write-Host '3/3 Membuat arsip ...'
    & $tar -czf $arsip -C $kerja database.sql media INFO.txt
    if ($LASTEXITCODE -ne 0) { throw "tar gagal (kode $LASTEXITCODE)." }

    $ukuran = [math]::Round((Get-Item $arsip).Length / 1MB, 2)
    Write-Host ''
    Write-Host "Selesai: $arsip ($ukuran MB; $jumlahBerkas berkas media)" -ForegroundColor Green
    Write-Host 'Unggah ke server (mis. scp ke /tmp), lalu jalankan deploy/impor-data.sh. Arsip berisi data pribadi: simpan aman.'
}
finally {
    if (Test-Path $cnf) { Remove-Item $cnf -Force }
    if (Test-Path $kerja) { Remove-Item $kerja -Recurse -Force }
}
