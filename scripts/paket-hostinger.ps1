# Membuat paket rilis E-Model PING untuk shared hosting (mis. Hostinger Premium) yang tidak
# menyediakan Node/Composer: aset frontend sudah di-build dan folder vendor sudah terisi (tanpa paket dev).
#
# Pemakaian (PowerShell, dari folder proyek):
#   powershell -ExecutionPolicy Bypass -File scripts\paket-hostinger.ps1
#
# Hasil: paket\e-model-ping-hostinger-YYYYMMDD-HHMMSS.zip (folder e-model-ping/ di dalamnya).
# Unggah ke server, lalu jalankan deploy/hostinger/pasang-rilis.sh (lihat docs/deploy-hostinger.md).
# Paket TIDAK berisi .env, unggahan media, log, maupun data database.
param(
    [string]$Php = 'C:\xampp3\php85\php.exe',
    [string]$Composer = 'C:\composer\composer.phar',
    [string]$Tujuan = ''
)
$ErrorActionPreference = 'Stop'
$akar = Split-Path -Parent $PSScriptRoot
Set-Location $akar

if (-not (Test-Path $Php)) { throw "PHP tidak ditemukan: $Php (atur -Php)" }
if (-not (Test-Path $Composer)) { throw "composer.phar tidak ditemukan: $Composer (atur -Composer)" }
if (Test-Path 'public\hot') { throw 'public\hot ada (npm run dev masih berjalan?). Hentikan dev server lalu hapus public\hot.' }
$tar = Join-Path $env:SystemRoot 'System32\tar.exe'
if (-not (Test-Path $tar)) { throw 'tar.exe bawaan Windows tidak ditemukan (butuh Windows 10 1803+).' }

$kotor = git status --porcelain
if ($kotor) { Write-Warning 'Ada perubahan yang belum di-commit; paket memakai isi folder kerja saat ini.' }
$commit = (git rev-parse --short HEAD).Trim()
$stempel = Get-Date -Format 'yyyyMMdd-HHmmss'
if (-not $Tujuan) { $Tujuan = Join-Path $akar 'paket' }
New-Item -ItemType Directory -Force -Path $Tujuan | Out-Null
$zip = Join-Path $Tujuan "e-model-ping-hostinger-$stempel.zip"
$kerja = Join-Path $env:TEMP "e-model-ping-paket-$stempel"
$isi = Join-Path $kerja 'e-model-ping'

# Berkas/folder khusus pengembangan yang tidak ikut paket (satu sumber, juga dipakai CI).
$polaBuang = (Get-Content (Join-Path $akar 'deploy\hostinger\pola-buang-paket.txt') | Where-Object { $_.Trim() }) -join '|'

try {
    Write-Host '1/5 Build aset frontend (npm run build) ...'
    cmd /c 'npm run build' | Out-Host
    if ($LASTEXITCODE -ne 0) { throw "npm run build gagal (kode $LASTEXITCODE)." }

    Write-Host '2/5 Menyalin berkas aplikasi ...'
    $daftar = git ls-files -co --exclude-standard | Where-Object { $_ -notmatch $polaBuang }
    $jumlah = 0
    foreach ($rel in $daftar) {
        $sumber = Join-Path $akar ($rel -replace '/', '\')
        if (-not (Test-Path -LiteralPath $sumber -PathType Leaf)) { continue }
        $tujuanBerkas = Join-Path $isi ($rel -replace '/', '\')
        $folder = Split-Path -Parent $tujuanBerkas
        if (-not (Test-Path $folder)) { New-Item -ItemType Directory -Force -Path $folder | Out-Null }
        Copy-Item -LiteralPath $sumber -Destination $tujuanBerkas
        $jumlah++
    }
    robocopy (Join-Path $akar 'public\build') (Join-Path $isi 'public\build') /E /NFL /NDL /NJH /NJS /NC /NS /NP | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "Gagal menyalin public\build (robocopy $LASTEXITCODE)." }
    robocopy (Join-Path $akar 'vendor') (Join-Path $isi 'vendor') /E /NFL /NDL /NJH /NJS /NC /NS /NP | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "Gagal menyalin vendor (robocopy $LASTEXITCODE)." }
    $global:LASTEXITCODE = 0

    Write-Host '3/5 Composer: buang paket dev & autoload produksi ...'
    # Composer menulis kemajuan ke stderr; di PowerShell 5.1 itu dianggap galat bila ErrorAction=Stop.
    $ErrorActionPreference = 'Continue'
    & $Php $Composer install --working-dir="$isi" --no-dev --optimize-autoloader --no-interaction --no-progress --no-ansi 2>&1 | ForEach-Object { "$_" } | Out-Host
    $kodeComposer = $LASTEXITCODE
    $ErrorActionPreference = 'Stop'
    if ($kodeComposer -ne 0) { throw "composer install gagal (kode $kodeComposer)." }
    Remove-Item (Join-Path $isi 'bootstrap\cache\*.php') -Force -ErrorAction SilentlyContinue

    Write-Host '4/5 Menulis VERSI.txt ...'
    @(
        'E-Model PING - paket shared hosting',
        "Commit   : $commit$(if ($kotor) { ' (+ perubahan belum di-commit)' })",
        "Dibuat   : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss zzz')",
        'Butuh    : PHP >= 8.4.1 (pdo_mysql, mbstring, gd, intl, zip, bcmath, fileinfo), MariaDB >= 10.4 atau MySQL 8.4',
        'Pasang   : APP=~/domains/<domain>/aplikasi bash e-model-ping/deploy/hostinger/pasang-rilis.sh <paket.zip>'
    ) | Set-Content -Path (Join-Path $isi 'VERSI.txt') -Encoding ASCII

    Write-Host '5/5 Membuat ZIP ...'
    & $tar -a -c -f $zip -C $kerja e-model-ping
    if ($LASTEXITCODE -ne 0) { throw "tar gagal (kode $LASTEXITCODE)." }

    $ukuran = [math]::Round((Get-Item $zip).Length / 1MB, 1)
    Write-Host ''
    Write-Host "Selesai: $zip ($ukuran MB, $jumlah berkas aplikasi + vendor + aset build; commit $commit)" -ForegroundColor Green
    Write-Host 'Langkah berikut: unggah ZIP ke server (File Manager hPanel atau scp -P 65002), lalu jalankan pasang-rilis.sh.'
}
finally {
    if (Test-Path $kerja) { Remove-Item $kerja -Recurse -Force }
}
