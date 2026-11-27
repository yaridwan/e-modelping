<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SkemaDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private const TABEL_DOMAIN = [
        'pengguna', 'pengaturan_website', 'bagian_beranda', 'menu_navigasi', 'halaman_informasi', 'bab_modul',
        'subbab_modul', 'berkas_media', 'versi_dokumen', 'fase_ping', 'langkah_fase', 'aspek_karakter',
        'indikator_karakter', 'referensi_pustaka', 'galeri_kegiatan', 'pertanyaan_umum', 'tautan_sosial',
        'pesan_kontak', 'kunjungan_halaman', 'aktivitas_dokumen', 'log_aktivitas', 'pengalihan_url', 'token_atur_ulang_sandi',
    ];

    /**
     * Basis data yang didukung: MySQL 8.4 (VPS) dan MariaDB ≥ 10.4 (shared hosting, mis. Hostinger).
     * CI menjalankan seluruh tes di keduanya.
     */
    public function test_basis_data_adalah_mysql_atau_mariadb_innodb_utf8mb4(): void
    {
        $driver = DB::connection()->getDriverName();
        $versi = DB::selectOne('select version() as v')->v;
        if ($driver === 'mariadb' || str_contains(strtolower($versi), 'mariadb')) {
            $this->assertMatchesRegularExpression('/^(10\.(4|5|6|11)|1[1-9])\./', $versi, 'MariaDB yang didukung: 10.4 ke atas');
        } else {
            $this->assertSame('mysql', $driver);
            $this->assertMatchesRegularExpression('/^8\.4\./', $versi, 'MySQL yang didukung: 8.4');
        }
        $tabel = DB::select('select table_name as t, engine as e, table_collation as c from information_schema.tables where table_schema = database()');
        foreach ($tabel as $t) {
            $this->assertSame('InnoDB', $t->e, "Tabel {$t->t} harus InnoDB");
            $this->assertStringStartsWith('utf8mb4', $t->c);
        }
    }

    public function test_semua_tabel_aplikasi_berbahasa_indonesia_tanpa_tabel_bawaan_inggris(): void
    {
        $ada = collect(DB::select('select table_name as t from information_schema.tables where table_schema = database()'))->pluck('t')->sort()->values()->all();
        $harapan = collect([...self::TABEL_DOMAIN, 'riwayat_migrasi'])->sort()->values()->all();
        $this->assertSame($harapan, $ada);
        foreach (['users', 'password_reset_tokens', 'sessions', 'jobs', 'cache', 'migrations', 'failed_jobs'] as $inggris) {
            $this->assertFalse(Schema::hasTable($inggris), "Tabel {$inggris} tidak boleh ada");
        }
    }

    public function test_tidak_ada_kolom_inggris_umum(): void
    {
        $kolom = collect(DB::select('select table_name as t, column_name as c from information_schema.columns where table_schema = database()'));
        $terlarang = ['email', 'password', 'title', 'description', 'user_id', 'created_at', 'updated_at', 'deleted_at', 'name', 'remember_token', 'migration', 'batch'];
        $pelanggaran = $kolom->filter(fn ($k) => in_array($k->c, $terlarang, true))->map(fn ($k) => "{$k->t}.{$k->c}")->values()->all();
        $this->assertSame([], $pelanggaran);
        $this->assertSame(['id', 'nama_migrasi', 'kelompok'], Schema::getColumnListing('riwayat_migrasi'));
    }

    public function test_riwayat_migrasi_terisi_oleh_migrator(): void
    {
        $this->assertSame(23, DB::table('riwayat_migrasi')->count());
        $this->assertTrue(DB::table('riwayat_migrasi')->where('nama_migrasi', '2026_11_26_000001_buat_tabel_pengguna')->exists());
    }

    public function test_constraint_satu_versi_aktif_dan_nomor_bab(): void
    {
        $indeks = collect(DB::select("show index from versi_dokumen where Key_name = 'versi_dokumen_satu_aktif_unique'"));
        $this->assertNotEmpty($indeks);
        $this->expectException(QueryException::class);
        DB::table('bab_modul')->insert(['nomor_bab' => 10, 'nomor_romawi' => 'X', 'judul' => 'x', 'slug' => 'x', 'urutan' => 10, 'status' => 'draf']);
    }

    public function test_singleton_pengaturan_dijamin_unique(): void
    {
        DB::table('pengaturan_website')->insert(['kunci_tunggal' => 'utama', 'nama_aplikasi' => 'A', 'nama_singkat' => 'A']);
        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('pengaturan_website')->insert(['kunci_tunggal' => 'utama', 'nama_aplikasi' => 'B', 'nama_singkat' => 'B']);
    }
}
