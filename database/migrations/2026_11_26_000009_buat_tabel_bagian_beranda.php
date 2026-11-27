<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bagian_beranda', function (Blueprint $table) {
            $table->id();
            $table->string('kode_bagian', 40)->unique();
            $table->enum('jenis_bagian', ['hero', 'pengantar', 'fase', 'aspek', 'bab', 'panduan', 'profil', 'galeri', 'faq', 'cta']);
            $table->string('judul', 191);
            $table->string('subjudul', 255)->nullable();
            $table->text('ringkasan')->nullable();
            $table->text('isi')->nullable();
            $table->string('teks_tombol_utama', 60)->nullable();
            $table->string('tautan_tombol_utama', 255)->nullable();
            $table->string('teks_tombol_pendamping', 60)->nullable();
            $table->string('tautan_tombol_pendamping', 255)->nullable();
            $table->foreignId('media_id')->nullable()->constrained('berkas_media')->nullOnDelete();
            // Struktur divalidasi per jenis bagian (lihat App\Support\SkemaBagianBeranda).
            $table->json('pengaturan_tampilan')->nullable();
            $table->unsignedSmallInteger('urutan')->unique();
            $table->boolean('aktif')->default(true);
            $table->auditPengguna();
            $table->stempelWaktu();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bagian_beranda');
    }
};
