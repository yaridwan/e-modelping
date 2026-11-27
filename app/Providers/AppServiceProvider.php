<?php

namespace App\Providers;

use App\Models\Pengguna;
use App\Support\Database\RepositoriMigrasi;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Riwayat migrasi memakai tabel `riwayat_migrasi` (nama_migrasi, kelompok).
        $this->app->extend('migration.repository', function ($repository, Application $app) {
            return new RepositoriMigrasi($app['db'], $app['config']['database.migrations.table'] ?? 'riwayat_migrasi');
        });
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        // Mencegah migrate:fresh, db:wipe, dan sejenisnya di produksi.
        DB::prohibitDestructiveCommands($this->app->isProduction());

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(12)->mixedCase()->letters()->numbers()->symbols()
            : Password::min(8)->letters()->numbers());

        $this->daftarkanMakroSkema();
        $this->daftarkanPembatasLaju();

        // Website personal: satu peran admin. Semua akses kelola memerlukan akun aktif.
        Gate::define('kelola-website', fn (Pengguna $pengguna): bool => $pengguna->aktif());
    }

    private function daftarkanMakroSkema(): void
    {
        Blueprint::macro('stempelWaktu', function (): void {
            /** @var Blueprint $this */
            $this->dateTime('dibuat_pada')->nullable();
            $this->dateTime('diperbarui_pada')->nullable();
        });

        Blueprint::macro('auditPengguna', function (): void {
            /** @var Blueprint $this */
            // Tanpa cascade: mengubah/menghapus akun tidak menghapus konten.
            $this->foreignId('dibuat_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $this->foreignId('diperbarui_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
        });
    }

    private function daftarkanPembatasLaju(): void
    {
        RateLimiter::for('kontak', fn (Request $request) => [
            Limit::perMinute(3)->by('kontak|'.$request->ip()),
            Limit::perDay(20)->by('kontak-harian|'.$request->ip()),
        ]);

        RateLimiter::for('lupa-sandi', fn (Request $request) => [
            Limit::perMinute(3)->by('lupa-sandi|'.$request->ip()),
            Limit::perHour(10)->by('lupa-sandi-jam|'.$request->ip()),
        ]);

        RateLimiter::for('atur-ulang-sandi', fn (Request $request) => Limit::perMinute(5)->by('atur-ulang|'.$request->ip()));

        RateLimiter::for('captcha', fn (Request $request) => Limit::perMinute(20)->by('captcha|'.$request->ip()));

        RateLimiter::for('dokumen', fn (Request $request) => Limit::perMinute(120)->by('dokumen|'.$request->ip()));
    }
}
