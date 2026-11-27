<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Menguji repository riwayat migrasi berbahasa Indonesia melalui perintah Artisan nyata
 * (migrate:fresh, status, rollback, migrate). Tidak memakai transaksi uji karena DDL MySQL
 * melakukan commit implisit.
 */
class PerintahMigrasiTest extends TestCase
{
    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_migrate_status_rollback_dan_fresh_memakai_repository_indonesia(): void
    {
        $this->assertSame(0, Artisan::call('migrate:fresh', ['--force' => true]));
        $this->assertSame(23, DB::table('riwayat_migrasi')->count());
        $this->assertSame(1, (int) DB::table('riwayat_migrasi')->max('kelompok'));

        Artisan::call('migrate:status');
        $keluaran = Artisan::output();
        $this->assertStringContainsString('2026_11_26_000023_buat_tabel_pengalihan_url', $keluaran);
        $this->assertStringContainsString('Ran', $keluaran);

        $this->assertSame(0, Artisan::call('migrate:rollback', ['--step' => 1, '--force' => true]));
        $this->assertFalse(Schema::hasTable('pengalihan_url'));
        $this->assertSame(22, DB::table('riwayat_migrasi')->count());

        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        $this->assertTrue(Schema::hasTable('pengalihan_url'));
        $this->assertSame(2, (int) DB::table('riwayat_migrasi')->where('nama_migrasi', '2026_11_26_000023_buat_tabel_pengalihan_url')->value('kelompok'));

        $this->assertSame(0, Artisan::call('migrate:fresh', ['--force' => true]));
        $this->assertSame(23, DB::table('riwayat_migrasi')->count());
    }
}
