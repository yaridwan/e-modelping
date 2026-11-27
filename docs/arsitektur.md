# Arsitektur — E-Model PING

## Gambaran

Monolit **Laravel 13 + Inertia 3 + React 19 (TypeScript)** dengan **SSR**. Laravel memegang routing, autentikasi, otorisasi (Gate `kelola-website`), validasi (Form Request), query, dan penyusunan props (Resource/DTO). React hanya merender. Tidak ada API JWT.

```
Browser ──HTTP──► Nginx ──FastCGI──► PHP-FPM (Laravel)
                                     │  ├─ Controller (ringkas) → Layanan domain → Model Eloquent → MySQL 8.4
                                     │  ├─ Resource/DTO → props Inertia (hanya data terbit/izin)
                                     │  └─ HTTP lokal ──► Node SSR (127.0.0.1:13714) → HTML React
                                     └─ storage/app/private (PDF & gambar, dilayani via controller)
```

## Lapisan backend

| Lapisan        | Lokasi                                       | Isi                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| -------------- | -------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Rute           | `routes/web.php`                             | 122 rute: publik, auth admin (tanpa registrasi), panel admin (`auth`, `auth.session`, `admin.aktif`, `can:kelola-website`)                                                                                                                                                                                                                                                                                                                          |
| Middleware     | `app/Http/Middleware`                        | `HeaderKeamanan` (CSP+nonce, nosniff, HSTS, noindex admin), `AlihkanJalurLama` (301), `HandleAppearance` (tema awal), `HandleInertiaRequests` (props bersama tanpa data privat), `CatatKunjunganPublik` (statistik setelah respons), `PastikanAdminAktif`                                                                                                                                                                                           |
| Form Request   | `app/Http/Requests`                          | Validasi + pesan Indonesia, URL aman, skema JSON beranda                                                                                                                                                                                                                                                                                                                                                                                            |
| Controller     | `app/Http/Controllers/{Publik,Admin,Auth}`   | Tipis; delegasi ke layanan                                                                                                                                                                                                                                                                                                                                                                                                                          |
| Layanan domain | `app/Layanan`                                | `LayananCaptcha`, `LayananAturUlangSandi`, `LayananMedia` (validasi isi, simpan atomik, penggunaan, izin publik), `LayananDokumen` (draf, aktivasi transaksional, sajian Range), `LayananPublikasi` (status, slug→301), `LayananStatistik` (dedup, agregasi, retensi), `LayananSeo`, `LayananPencarian`, `LayananNavigasi`, `LayananInventaris`, `LayananUrutan`, `LayananAudit`, `LayananSanitasiHtml`, `LayananPengaturan` (cache + versi konten) |
| Resource/DTO   | `app/Http/Resources`                         | `BabRingkas`, `FasePublik`, `AspekPublik`, `MediaPublik`, `VersiDokumenPublik`, `ProfilPenyusun`                                                                                                                                                                                                                                                                                                                                                    |
| Model          | `app/Models`                                 | `ModelDasar` (dibuat_pada/diperbarui_pada), `Pengguna` (kontrak auth Indonesia), 22 model domain; trait `MenyegarkanCachePublik`                                                                                                                                                                                                                                                                                                                    |
| Adaptasi skema | `app/Support/Database/RepositoriMigrasi.php` | Riwayat migrasi `riwayat_migrasi(nama_migrasi, kelompok)`                                                                                                                                                                                                                                                                                                                                                                                           |

## Pemetaan kontrak Laravel ke kolom Indonesia

| Kontrak Laravel      | Implementasi                                                                                                                                                                              |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Tabel user           | `Pengguna::$table = 'pengguna'`, provider `pengguna` di `config/auth.php`                                                                                                                 |
| Identitas login      | kredensial `['surel' => …, 'password' => …]` (kunci `password` hanya untuk hasher)                                                                                                        |
| Hash sandi           | `getAuthPasswordName()` → `kata_sandi` (dipakai juga untuk rehash & `logoutOtherDevices`)                                                                                                 |
| Remember token       | `getRememberTokenName()` → `token_pengingat`                                                                                                                                              |
| Timestamps           | `CREATED_AT = 'dibuat_pada'`, `UPDATED_AT = 'diperbarui_pada'` (append-only: `UPDATED_AT = null`)                                                                                         |
| Reset sandi          | Broker bawaan **tidak** dipakai; `LayananAturUlangSandi` + tabel `token_atur_ulang_sandi` (HMAC, TTL 60 menit, sekali pakai), notifikasi `TautanAturUlangSandi` dengan URL dari `APP_URL` |
| Migration repository | `RepositoriMigrasi` di-_extend_ pada binding `migration.repository` (AppServiceProvider)                                                                                                  |
| Sesi/cache/antrean   | `file` / `file` / `sync` — tidak ada tabel infrastruktur                                                                                                                                  |

## Frontend

- `resources/js/app.tsx`: resolusi layout (`publik/*` → `PublikLayout`, `admin/*` → `AdminLayout`, `auth/*` → `AuthLayout`).
- SSR: plugin `@inertiajs/vite` memakai `app.tsx` sebagai entri SSR → `bootstrap/ssr/app.js` (`npm run build:ssr`), dijalankan `php artisan inertia:start-ssr`. Root Blade (`resources/views/app.blade.php`) menulis `<title>`, meta, canonical, OG/Twitter, dan JSON-LD dari prop `seo` dengan atribut `data-inertia` sehingga metadata ada di HTML awal walau SSR mati; `<Seo>` React memperbaruinya saat navigasi klien.
- Komponen khusus browser aman SSR: `KlienSaja` (grafik), `PenampilPdf` (dimuat malas setelah klik), Tiptap (`immediatelyRender: false`), akses `localStorage` lewat `penyimpanan` (try/catch).
- **Motion** (`motion/react`): kartu (`Muncul`, aman tanpa JS), drawer/Sheet, form feedback (`PesanGalat`, status kontak), transisi panel.
- **Anime.js**: hanya `IlustrasiFase` (SVG beranda), di-_import_ dinamis, `createScope({root})`, `revert()` saat unmount, dijeda di luar viewport, tidak berjalan bila `prefers-reduced-motion`. Motion tidak menyentuh elemen di dalam SVG tersebut.
- **shadcn/ui** (Radix + Tailwind 4) dan **shadcn Charts** (`components/ui/chart.tsx` di atas Recharts 3) — grafik lokal di browser, bukan layanan analitik.

## Design system

Token di `resources/css/app.css` (`:root` dan `.dark`): primary `#BE185D`, aksen `#EC4899`, latar `#FFF7FB`, teks `#1F1720`; gelap: latar `#121013`, kartu `#1B171C`, primary pink terang. Warna merek dari pengaturan diinjeksi sebagai variabel CSS tervalidasi (`--merek`, `--merek-2`). Tipografi Plus Jakarta Sans (diunduh saat build, disajikan lokal). Kelas `.materi` mengatur tipografi baca (ukuran via `--ukuran-baca`, lebar via preferensi). Tema: cookie `appearance` + `localStorage`, skrip inline ber-nonce menerapkan tema sebelum render; profil admin menyimpan `tema_pilihan`.

## Cache & invalidasi

`LayananPengaturan::ingat()` memakai kunci yang menyertakan **versi konten**; setiap simpan/hapus model publik (trait) dan aksi publikasi menaikkan versi, sehingga navigasi, beranda, sitemap, peta pengalihan, dan izin media publik dibangun ulang. Pengaturan identitas dibersihkan saat disimpan. SSR tidak di-cache.

## Keamanan (ringkas)

CSRF bawaan; query Eloquent terparameter; `$fillable` eksplisit (kolom sensitif via `forceFill`); sanitasi HTML Symfony HtmlSanitizer; URL aman (`urlAman`); embed video hanya YouTube-nocookie/Vimeo tanpa fetch server; CSP dengan nonce; cookie sesi HttpOnly + SameSite=Lax (Secure di produksi); rate limit login (identitas+IP dan IP), kontak, lupa/atur ulang sandi, dokumen; captcha aritmetika CSPRNG sekali pakai; `DB::prohibitDestructiveCommands()` di produksi.
