<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('halaman_informasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_halaman', 60)->unique();
            $table->string('judul', 191);
            $table->string('slug', 191)->unique();
            $table->text('ringkasan')->nullable();
            $table->longText('isi')->nullable();
            $table->foreignId('gambar_sampul_id')->nullable()->constrained('berkas_media')->nullOnDelete();
            $table->string('judul_seo', 191)->nullable();
            $table->string('deskripsi_seo', 300)->nullable();
            $table->foreignId('gambar_og_id')->nullable()->constrained('berkas_media')->nullOnDelete();
            $table->enum('status', ['draf', 'terbit', 'arsip'])->default('draf')->index();
            $table->dateTime('diterbitkan_pada')->nullable();
            $table->boolean('tampilkan_di_navigasi')->default(false);
            // Halaman inti (tentang, panduan, kontak, privasi, dsb.) tidak dapat dihapus.
            $table->boolean('halaman_inti')->default(false);
            $table->auditPengguna();
            $table->stempelWaktu();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('halaman_informasi');
    }
};
