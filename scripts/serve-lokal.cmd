@echo off
rem ==========================================================================
rem  Menjalankan E-Model PING di komputer lokal (Command Prompt / CMD).
rem  - PHP 8.4.1+ : C:\xampp3\php85\php.exe (PHP 8.2 bawaan XAMPP TIDAK cukup)
rem  - MySQL 8.4  : C:\mysql84 (port 3307), dinyalakan otomatis bila belum jalan
rem  - SSR Node   : php artisan inertia:start-ssr (port 13714), jendela terpisah
rem  - Web        : php artisan serve (http://127.0.0.1:8000)
rem  Batas unggah 30 MB diambil dari scripts\php-lokal\ping.ini.
rem ==========================================================================
setlocal
cd /d "%~dp0.."

set "PHP_DIR=C:\xampp3\php85"
set "MYSQL_DIR=C:\mysql84"

if not exist "%PHP_DIR%\php.exe" (
    echo [GAGAL] PHP 8.4+ tidak ditemukan di %PHP_DIR%. Ubah PHP_DIR di skrip ini.
    exit /b 1
)
set "PATH=%PHP_DIR%;%PATH%"
set "PHP_INI_SCAN_DIR=%CD%\scripts\php-lokal"

rem --- 1. MySQL 8.4 -----------------------------------------------------------
netstat -ano | findstr /R /C:":3307 .*LISTENING" >nul
if errorlevel 1 (
    if not exist "%MYSQL_DIR%\bin\mysqld.exe" (
        echo [GAGAL] MySQL 8.4 tidak ditemukan di %MYSQL_DIR%.
        exit /b 1
    )
    echo Menyalakan MySQL 8.4 di port 3307 ...
    start "MySQL 8.4 - E-Model PING" /MIN "%MYSQL_DIR%\bin\mysqld.exe" --defaults-file=%MYSQL_DIR%\my-ping.ini
    rem Jeda ~6 detik agar MySQL siap (ping dipakai karena timeout gagal tanpa input interaktif).
    ping -n 7 127.0.0.1 >nul
) else (
    echo MySQL 8.4 sudah berjalan di port 3307.
)

rem --- 2. Server SSR Inertia --------------------------------------------------
if not exist "bootstrap\ssr\app.js" (
    echo Bundle SSR belum ada, menjalankan npm run build:ssr ...
    call npm run build:ssr || exit /b 1
)
netstat -ano | findstr /R /C:":13714 .*LISTENING" >nul
if errorlevel 1 (
    echo Menyalakan server SSR di port 13714 ...
    rem PATH sudah berisi PHP 8.5 (baris di atas) dan diwariskan ke jendela baru.
    rem Jangan menulis %%PATH%% di dalam blok if: tanda ")" pada "Program Files (x86)" memutus blok.
    start "SSR Inertia - E-Model PING" /MIN cmd /c "php artisan inertia:start-ssr"
) else (
    echo Server SSR sudah berjalan di port 13714.
)

rem --- 3. Server web ----------------------------------------------------------
echo.
echo Buka http://127.0.0.1:8000  (admin: http://127.0.0.1:8000/admin/masuk)
echo Tekan Ctrl+C untuk menghentikan server web.
echo.
php artisan serve --host=127.0.0.1
