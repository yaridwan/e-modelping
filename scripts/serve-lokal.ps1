# Menjalankan server pengembangan lokal E-Model PING (Windows/PowerShell).
# - PHP 8.4.1+ (contoh: C:\xampp3\php85\php.exe; PHP 8.2 bawaan XAMPP tidak cukup) dengan batas unggah 30 MB dari scripts/php-lokal/ping.ini
# - Server SSR Inertia (Node 22+) di 127.0.0.1:13714
param(
    [string]$Php = 'C:\xampp3\php85\php.exe',
    [int]$Port = 8000
)
$akar = Split-Path -Parent $PSScriptRoot
$env:PHP_INI_SCAN_DIR = Join-Path $akar 'scripts\php-lokal'
Set-Location $akar
Start-Process -NoNewWindow -FilePath $Php -ArgumentList 'artisan', 'inertia:start-ssr'
& $Php artisan serve --host=127.0.0.1 --port=$Port
