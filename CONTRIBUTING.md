# Panduan Kontribusi

Terima kasih ingin membantu E-Model PING. Proyek ini adalah website e-modul personal dengan satu pengelola; kontribusi berupa laporan bug, usulan, dan perbaikan kode tetap disambut.

## Sebelum mulai

- **Bug/usulan:** buka [issue](https://github.com/yaridwan/e-modelping/issues/new/choose) memakai templat yang tersedia.
- **Celah keamanan:** jangan lewat issue — ikuti [SECURITY.md](SECURITY.md).
- **Materi modul** (teks & PDF) adalah hak cipta penyusun; usulan koreksi materi disampaikan lewat issue, bukan diubah langsung.

## Menyiapkan lingkungan

Ikuti [README §10–§13](README.md#10-persyaratan): PHP ≥ 8.4.1, Composer 2, MySQL 8.4, Node.js ≥ 22. Buat database uji `e_model_ping_testing` (dipakai `phpunit.xml`).

## Standar kode

- **PHP:** Laravel 13, gaya [Pint](https://laravel.com/docs/pint) (`pint.json`), tipe eksplisit, validasi di FormRequest, logika domain di `app/Layanan`.
- **Frontend:** React 19 + TypeScript strict, Inertia 3, Tailwind 4, komponen shadcn/ui; format & lint oleh Vite+ (oxlint/oxfmt).
- **Bahasa Indonesia** untuk nama tabel, kolom, model, layanan, rute, label UI, dan pesan validasi (mis. `dibuat_pada`, `surel`, `kata_sandi`). Lihat [docs/kamus-data.md](docs/kamus-data.md).
- **Keamanan & privasi:** jangan menyimpan IP mentah, jangan menulis isi pesan ke log, sanitasi HTML dari editor, jangan menambah pelacak pihak ketiga.
- **Aksesibilitas:** satu `<h1>` per halaman, label untuk setiap kontrol, fokus terlihat, hormati `prefers-reduced-motion`.

## Pemeriksaan sebelum pull request

```bash
composer lint:check        # Pint
npm run check              # lint + format
npm run types:check        # tsc --noEmit
php artisan test           # PHPUnit (MySQL e_model_ping_testing)
npm run build:ssr          # build klien + SSR
```

Atau sekaligus: `composer ci:check`. Jangan menjalankan `php artisan config:cache` saat tes berjalan (tes bisa membaca konfigurasi DB utama); jalankan `php artisan optimize:clear` sebelum tes.

## Alur kerja

1. Fork → buat cabang dari `main` (`perbaikan/…`, `fitur/…`, `dok/…`).
2. Commit kecil dengan pesan jelas berbahasa Indonesia, mis. `Perbaiki urutan subbab saat diseret`.
3. Tambahkan/ubah tes untuk perilaku baru; perbarui README/docs bila perlu.
4. Buka pull request dengan mengisi templat; pastikan CI hijau.

## Migrasi database

- Jangan mengubah migrasi yang sudah dirilis; buat migrasi baru (`2026_xx_xx_xxxxxx_…`).
- Ikuti penamaan Indonesia dan perbarui [docs/kamus-data.md](docs/kamus-data.md).
- Seeder data dasar harus aman diulang (tidak menimpa suntingan pemilik).
