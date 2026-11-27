<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Log audit baca-saja. Kolom `perubahan` hanya berisi nilai yang sudah disaring
        // (tanpa sandi, token, rahasia konfigurasi, maupun isi pesan lengkap).
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->string('aksi', 40)->index();
            $table->string('jenis_entitas', 60);
            $table->unsignedBigInteger('entitas_id')->nullable();
            $table->string('ringkasan', 255);
            $table->json('perubahan')->nullable();
            $table->char('sidik_pelaku', 64)->nullable();
            $table->string('kode_permintaan', 40)->nullable();
            $table->boolean('data_contoh')->default(false);
            $table->dateTime('dibuat_pada')->index();

            $table->index(['jenis_entitas', 'entitas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
