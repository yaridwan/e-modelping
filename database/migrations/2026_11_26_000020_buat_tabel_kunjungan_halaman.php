<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only: tidak ada kolom diperbarui_pada. Tidak menyimpan IP mentah, query, atau HTML.
        Schema::create('kunjungan_halaman', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_halaman', 30);
            $table->foreignId('bab_id')->nullable()->constrained('bab_modul');
            $table->foreignId('halaman_id')->nullable()->constrained('halaman_informasi')->nullOnDelete();
            $table->string('jalur_halaman', 191);
            $table->string('asal_domain', 191)->nullable();
            $table->char('sidik_pengunjung', 64);
            // Tanggal lokal (zona waktu pengaturan) sebagai ember agregasi & deduplikasi harian.
            $table->date('tanggal_kunjungan');
            $table->enum('jenis_perangkat', ['desktop', 'tablet', 'ponsel', 'lainnya'])->default('lainnya');
            $table->boolean('data_contoh')->default(false);
            $table->dateTime('dibuat_pada');

            $table->unique(['sidik_pengunjung', 'jalur_halaman', 'tanggal_kunjungan'], 'kunjungan_dedup_unique');
            $table->index(['data_contoh', 'tanggal_kunjungan']);
            $table->index(['bab_id', 'tanggal_kunjungan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kunjungan_halaman');
    }
};
