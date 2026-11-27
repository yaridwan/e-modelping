# Kamus Data — basis data `e_model_ping`

MySQL 8.4 · InnoDB · `utf8mb4_unicode_ci`. Semua tabel/kolom aplikasi berbahasa Indonesia (`snake_case`). `id`, `uuid`, `slug`, `url` dipakai sebagai pengenal teknis baku. Waktu disimpan **UTC** (`DATETIME`) dan disajikan dalam zona `pengaturan_website.zona_waktu` (bawaan Asia/Jakarta). Kolom stempel waktu: `dibuat_pada`, `diperbarui_pada` (tabel _append-only_ hanya `dibuat_pada`). Tidak ada _soft delete_.

Konvensi kolom: **W** = wajib (NOT NULL), **O** = opsional (NULL). FK audit `dibuat_oleh`/`diperbarui_oleh` → `pengguna.id` **ON DELETE SET NULL** (mengubah/menghapus akun tidak menghapus konten).

> Validasi aplikasi ada di Form Request `app/Http/Requests/**`. Kolom di bawah mencantumkan validasi utama; rincian pesan galat berbahasa Indonesia ada di kelas tersebut.

## 1. `pengguna` — akun pemilik/admin

| Kolom                                                   | Tipe                      | W/O | Default | Indeks/Relasi            | Validasi & catatan                                 | Contoh                |
| ------------------------------------------------------- | ------------------------- | --- | ------- | ------------------------ | -------------------------------------------------- | --------------------- |
| id                                                      | BIGINT UNSIGNED           | W   | auto    | PK                       |                                                    | 1                     |
| nama_lengkap                                            | VARCHAR(150)              | W   |         |                          | wajib, ≤150                                        | Tita Sapriyanti       |
| nama_panggilan                                          | VARCHAR(60)               | O   |         |                          | ≤60                                                | Bu Tita               |
| surel                                                   | VARCHAR(191)              | W   |         | UNIQUE                   | email, unik; identitas login (tidak pernah publik) | admin@emodelping.test |
| surel_diverifikasi_pada                                 | DATETIME                  | O   |         |                          |                                                    |                       |
| kata_sandi                                              | VARCHAR(255)              | W   |         |                          | hash bcrypt (`getAuthPasswordName()`)              | `$2y$12$…`            |
| token_pengingat                                         | VARCHAR(100)              | O   |         |                          | remember token (`getRememberTokenName()`)          |                       |
| nomor_telepon                                           | VARCHAR(30)               | O   |         |                          | privat; regex angka/spasi/+                        |                       |
| jabatan, institusi, bidang_keahlian                     | VARCHAR                   | O   |         |                          | ≤120/150/191                                       | Penyusun              |
| biografi                                                | TEXT                      | O   |         |                          | ≤3000                                              |                       |
| foto_profil_id                                          | BIGINT UNSIGNED           | O   |         | FK→berkas_media SET NULL | gambar JPG/PNG/WebP ≤4 MB                          |                       |
| situs_pribadi                                           | VARCHAR(255)              | O   |         |                          | url http/https                                     |                       |
| surel_publik                                            | VARCHAR(191)              | O   |         |                          | email, terpisah dari surel akun                    |                       |
| nomor_kontak_publik                                     | VARCHAR(30)               | O   |         |                          |                                                    |                       |
| tampilkan_profil_publik / _foto_publik / _kontak_publik | TINYINT(1)                | W   | 0       |                          | pilihan publikasi granular                         | 1                     |
| tema_pilihan                                            | ENUM(terang,gelap,sistem) | W   | sistem  |                          |                                                    | sistem                |
| status                                                  | ENUM(aktif,nonaktif)      | W   | aktif   |                          | akun nonaktif dikeluarkan dari sesi                | aktif                 |
| wajib_ganti_sandi                                       | TINYINT(1)                | W   | 0       |                          |                                                    | 0                     |
| terakhir_masuk_pada, sandi_diubah_pada                  | DATETIME                  | O   |         |                          |                                                    |                       |

## 2. `pengaturan_website` — singleton

`kunci_tunggal` VARCHAR(20) **UNIQUE** default `utama` menjamin satu baris di tingkat DB. Kolom: `nama_aplikasi` (W, ≤120), `nama_singkat` (W, ≤40), `tagline`, `deskripsi_singkat`, `logo_terang_id`, `logo_gelap_id`, `favicon_id`, `gambar_sampul_id`, `gambar_og_id` (semua FK→berkas_media SET NULL, cadangan aset `public/images`), `nama_pemilik`, `nama_institusi`, `surel_kontak`, `nomor_whatsapp`, `alamat_publik`, `kota_kabupaten`, `provinsi`, `bahasa` (id), `zona_waktu` (Asia/Jakarta|Makassar|Jayapura), `warna_utama`/`warna_pendamping` CHAR(7) (regex `^#[0-9A-Fa-f]{6}$`, tidak menerima CSS mentah), `tema_bawaan` ENUM, `teks_footer`, `tahun_hak_cipta_awal` SMALLINT, `judul_seo_bawaan`, `templat_judul_seo` (wajib memuat `%judul%`), `deskripsi_seo_bawaan` (≤300), `jenis_identitas_penerbit` ENUM(orang, organisasi), `kode_verifikasi_google`/`_bing` (regex alfanumerik), `izinkan_unduh` (bool, global), `batas_unggah_mb` (1…`PING_BATAS_UNGGAH_MAKS_MB`), `panduan_penamaan_versi`, `statistik_aktif`, `kecualikan_admin`, `kecualikan_bot` (bool), `retensi_statistik_hari` (30–1825), `retensi_pesan_hari` (30–3650), `diperbarui_oleh`. **Domain kanonis tidak disimpan di sini** — berasal dari `APP_URL`.

## 3. `bagian_beranda`

`kode_bagian` UNIQUE; `jenis_bagian` ENUM(hero, pengantar, fase, aspek, bab, panduan, profil, galeri, faq, cta); `judul` (W), `subjudul`, `ringkasan`, `isi`, `teks_tombol_utama`/`tautan_tombol_utama`, `teks_tombol_pendamping`/`tautan_tombol_pendamping` (URL divalidasi: http/https/mailto/jalur internal), `media_id` FK SET NULL, `pengaturan_tampilan` JSON (struktur per jenis di `App\Support\SkemaBagianBeranda`, disaring ke kunci yang dikenal), `urutan` UNIQUE, `aktif`, audit. Data fase/bab/FAQ/galeri tidak diduplikasi di JSON.

## 4. `menu_navigasi`

`induk_id` FK→menu_navigasi CASCADE (submenu ikut terhapus), `lokasi` ENUM(utama, footer), `label`, `jenis_tautan` ENUM(halaman, bab, internal, eksternal), `halaman_id` FK CASCADE, `bab_id` FK (RESTRICT bawaan), `url` VARCHAR(255), `ikon` (allowlist), `buka_tab_baru`, `urutan`, `aktif`. Indeks (lokasi, aktif, urutan). **Tepat satu target** dijamin `SimpanMenuRequest::dataTersaring()`; siklus induk, kedalaman >1, `javascript:`/`//host` ditolak. (CHECK DB tidak dipakai karena MySQL melarang CHECK pada kolom FK beraksi CASCADE.)

## 5. `halaman_informasi`

`kode_halaman` UNIQUE, `judul`, `slug` UNIQUE (slug sistem dilarang), `ringkasan`, `isi` LONGTEXT (HTML tersanitasi), `gambar_sampul_id`, `judul_seo`, `deskripsi_seo`, `gambar_og_id`, `status` ENUM(draf, terbit, arsip), `diterbitkan_pada`, `tampilkan_di_navigasi`, **`halaman_inti`** (tambahan: halaman inti tidak dapat dihapus/diubah slug), audit.

## 6. `bab_modul`

`nomor_bab` TINYINT UNIQUE + **CHECK 1–9**, `nomor_romawi`, `judul`, `slug` UNIQUE, `ringkasan`, `pengantar` LONGTEXT, `tujuan_pembelajaran` JSON (daftar string), `poin_penting` JSON, `gambar_sampul_id`, `urutan` UNIQUE, `status` ENUM + indeks, `diterbitkan_pada`, `judul_seo`, `deskripsi_seo`, `gambar_og_id`, **`metadata_sumber`** JSON (tambahan: provenance halaman PDF, internal), audit. Waktu baca dihitung dari isi terbit (200 kata/menit), tidak disimpan.

## 7. `subbab_modul`

`bab_id` FK (RESTRICT), `kode_subbab` (mis. `IV-A`), `judul`, `slug`, `ringkasan`, `isi` LONGTEXT (W), `contoh_praktik`, `media_id` FK SET NULL, `urutan`, `status`, `metadata_sumber` JSON, audit. **UNIQUE(bab_id, slug)** dan **UNIQUE(bab_id, urutan)**; pengurutan ulang memakai offset sementara dalam satu transaksi (`LayananUrutan`).

## 8. `berkas_media`

`uuid` UNIQUE, `judul`, `jenis_media` ENUM(gambar, pdf, video_eksternal), `nama_asli`, `nama_simpan` (buatan aplikasi), `lokasi_penyimpanan` (disk `local` = `storage/app/private`), `jalur_berkas`, `url_eksternal`, `tipe_mime`, `ekstensi`, `ukuran_bita`, `lebar_piksel`, `tinggi_piksel`, `jumlah_halaman`, `teks_alternatif`, `keterangan`, `sumber`, `lisensi`, `checksum_sha256` (indeks), `diunggah_oleh` FK SET NULL. **CHECK** konsistensi jenis: video → `url_eksternal` wajib & tanpa jalur; gambar/pdf → `jalur_berkas` wajib & tanpa URL.

## 9. `versi_dokumen`

`bab_id` FK (RESTRICT), `jenis_dokumen` ENUM(bab, modul_lengkap), `berkas_id` FK (RESTRICT), `nomor_versi` (regex `^[0-9A-Za-z][0-9A-Za-z.\-]*$`), `judul_dokumen`, `catatan_perubahan`, `status`, `versi_aktif`, `izinkan_unduh`, `diterbitkan_pada`, `diterbitkan_oleh`, audit. Kolom tergenerasi: `lingkup_dokumen` = `CONCAT(jenis, ':', COALESCE(bab_id,0))` (UNIQUE bersama `nomor_versi`) dan `kunci_versi_aktif` = `IF(versi_aktif=1, lingkup, NULL)` **UNIQUE** → satu versi aktif per lingkup. CHECK: bab wajib `bab_id`, modul lengkap wajib `bab_id IS NULL`; versi aktif wajib berstatus terbit.

## 10–11. `fase_ping`, `langkah_fase`

`fase_ping`: `kode_fase` UNIQUE (PCG/ICTG/EDCG), `nama`, `slug` UNIQUE, `judul_indonesia`, `deskripsi`, `tujuan` JSON, `waktu_pelaksanaan`, `durasi_minimum_menit`, `durasi_maksimum_menit` (≥ minimum), `frekuensi_panduan`, `media_id`, `catatan_guru`, `indikator_keberhasilan` JSON, **`penguatan_karakter`** JSON `[{aspek, bentuk}]` (tambahan dari tabel sumber), `bab_id`, `urutan` UNIQUE, `status`.
`langkah_fase`: `fase_id` FK CASCADE, `nomor_langkah`, `judul`, `penjelasan`, `aktivitas_guru`, `aktivitas_anak`, `contoh_dialog` JSON `[{pembicara, ucapan}]`, `media_id`, `urutan`; UNIQUE(fase_id, nomor_langkah) & (fase_id, urutan).

## 12–13. `aspek_karakter`, `indikator_karakter`

`aspek_karakter`: `kode_aspek` UNIQUE, `nama`, `slug` UNIQUE, `deskripsi`, `contoh_pembiasaan`, **`fokus_pengamatan`** (Tabel 6.9), `ikon` (allowlist), `warna` (allowlist), `urutan` UNIQUE, `aktif`.
`indikator_karakter`: `aspek_id` FK, `nomor_indikator` UNIQUE + **CHECK 1–16**, `rumusan`, `contoh_pengamatan`, `fase_pengamatan` JSON (kode fase), `catatan_pendampingan`, `rubrik_bb/mb/bsh/bsb`, `urutan`, `aktif`. **Tidak ada FK ke anak/siswa dan tidak ada skor.**

## 14. `referensi_pustaka`

`penulis`, `tahun`, `judul`, `jenis_sumber` ENUM, `nama_penerbit_jurnal`, `volume`, `nomor_terbitan`, `halaman`, `doi` (regex `^10\.\d{4,9}/\S+$`, hanya bila diketahui), `url`, `tanggal_akses`, `catatan_editorial`, `status_verifikasi` ENUM(belum_diverifikasi, tercantum_di_sumber, terverifikasi, perlu_koreksi), `urutan`, `aktif`.

## 15–17. `galeri_kegiatan`, `pertanyaan_umum`, `tautan_sosial`

`galeri_kegiatan`: `slug` UNIQUE, `media_id` FK (RESTRICT), `bab_id`, `fase_id` SET NULL, `tanggal_kegiatan`, `lokasi_umum`, `keterangan`, `teks_alternatif` (W), `dasar_izin_publikasi` (wajib sebelum terbit), `status`, `urutan`, `diterbitkan_pada`, audit.
`pertanyaan_umum`: `kategori`, `pertanyaan`, `jawaban`, `urutan`, `aktif` (indeks).
`tautan_sosial`: `nama_platform` (allowlist), `label`, `url` (wajib bila aktif), `ikon`, `urutan`, `aktif` (bawaan nonaktif).

## 18. `pesan_kontak`

`nama_pengirim`, `surel_pengirim`, `institusi`, `subjek`, `isi_pesan` (≤3000, tag HTML dibuang), `persetujuan_privasi`, `status` ENUM(baru, dibaca, ditindaklanjuti, arsip), `catatan_admin`, `dibaca_pada`, `ditindaklanjuti_pada`, `sidik_pengirim` (HMAC pseudonim), `data_contoh`. Indeks (status, dibuat_pada), (data_contoh, dibuat_pada).

## 19–20. `kunjungan_halaman`, `aktivitas_dokumen` (append-only)

`kunjungan_halaman`: `jenis_halaman`, `bab_id`, `halaman_id` SET NULL, `jalur_halaman` (≤191, tanpa query), `asal_domain` (host perujuk saja), `sidik_pengunjung` CHAR(64), `tanggal_kunjungan` DATE (tanggal lokal), `jenis_perangkat` ENUM, `data_contoh`, `dibuat_pada`. **UNIQUE(sidik, jalur, tanggal)** = deduplikasi harian.
`aktivitas_dokumen`: `versi_dokumen_id` FK, `jenis_aktivitas` ENUM(baca, unduh), `sidik_pengunjung`, `jenis_perangkat`, **`tanggal_aktivitas`** (tambahan untuk deduplikasi), `data_contoh`, `dibuat_pada`. **UNIQUE(versi, jenis, sidik, tanggal)**.

## 21. `log_aktivitas` (baca-saja)

`pengguna_id` SET NULL, `aksi` (indeks), `jenis_entitas`, `entitas_id` (indeks gabungan), `ringkasan`, `perubahan` JSON tersaring (sandi/token/isi pesan → `[disembunyikan]`, teks panjang diringkas), `sidik_pelaku`, `kode_permintaan` (UUID per permintaan), `data_contoh`, `dibuat_pada`.

## 22. `pengalihan_url`

`jalur_lama` UNIQUE, `jalur_baru`, `kode_status` (CHECK 301/302/308), `aktif`. Hanya jalur internal; loop dan rantai >5 ditolak; perubahan slug meratakan rantai otomatis.

## 23. `token_atur_ulang_sandi`

`pengguna_id` FK CASCADE, `token_hash` CHAR(64) UNIQUE (HMAC-SHA256 dengan APP_KEY), `kedaluwarsa_pada` (indeks, TTL 60 menit), `digunakan_pada`, `dibuat_pada`. Token mentah hanya di surel; sekali pakai.

## Infrastruktur: `riwayat_migrasi`

`id` INT, `nama_migrasi` VARCHAR(255), `kelompok` INT — dikelola `App\Support\Database\RepositoriMigrasi` (memetakan kontrak Migrator `migration`/`batch`). Diisi hanya oleh migrator. Tidak ada tabel `sessions`, `cache`, `jobs` (sesi & cache berbasis berkas, antrean `sync`).
