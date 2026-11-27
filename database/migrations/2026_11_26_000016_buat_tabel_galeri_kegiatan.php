<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galeri_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 191);
            $table->string('slug', 191)->unique();
            $table->text('ringkasan')->nullable();
            $table->foreignId('media_id')->constrained('berkas_media');
            $table->foreignId('bab_id')->nullable()->constrained('bab_modul');
            $table->foreignId('fase_id')->nullable()->constrained('fase_ping')->nullOnDelete();
            $table->date('tanggal_kegiatan')->nullable();
            $table->string('lokasi_umum', 150)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('teks_alternatif', 255);
            // Dokumentasi yang memuat anak hanya diterbitkan bila dasar izin publikasi dimiliki.
            $table->text('dasar_izin_publikasi')->nullable();
            $table->enum('status', ['draf', 'terbit', 'arsip'])->default('draf')->index();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->dateTime('diterbitkan_pada')->nullable();
            $table->auditPengguna();
            $table->stempelWaktu();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galeri_kegiatan');
    }
};
