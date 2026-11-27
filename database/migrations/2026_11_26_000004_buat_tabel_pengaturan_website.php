<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_website', function (Blueprint $table) {
            $table->id();
            // Singleton dijamin di tingkat database: hanya satu baris dengan kunci 'utama'.
            $table->string('kunci_tunggal', 20)->default('utama')->unique();
            $table->string('nama_aplikasi', 120);
            $table->string('nama_singkat', 40);
            $table->string('tagline', 191)->nullable();
            $table->text('deskripsi_singkat')->nullable();
            $table->foreignId('logo_terang_id')->nullable()->constrained('berkas_media')->nullOnDelete();
            $table->foreignId('logo_gelap_id')->nullable()->constrained('berkas_media')->nullOnDelete();
            $table->foreignId('favicon_id')->nullable()->constrained('berkas_media')->nullOnDelete();
            $table->foreignId('gambar_sampul_id')->nullable()->constrained('berkas_media')->nullOnDelete();
            $table->string('nama_pemilik', 150)->nullable();
            $table->string('nama_institusi', 150)->nullable();
            $table->string('surel_kontak', 191)->nullable();
            $table->string('nomor_whatsapp', 30)->nullable();
            $table->string('alamat_publik', 255)->nullable();
            $table->string('kota_kabupaten', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('bahasa', 10)->default('id');
            $table->string('zona_waktu', 40)->default('Asia/Jakarta');
            $table->char('warna_utama', 7)->default('#BE185D');
            $table->char('warna_pendamping', 7)->default('#EC4899');
            $table->enum('tema_bawaan', ['terang', 'gelap', 'sistem'])->default('sistem');
            $table->string('teks_footer', 500)->nullable();
            $table->unsignedSmallInteger('tahun_hak_cipta_awal')->nullable();
            $table->string('judul_seo_bawaan', 191)->nullable();
            $table->string('templat_judul_seo', 100)->default('%judul% | %nama%');
            $table->string('deskripsi_seo_bawaan', 300)->nullable();
            $table->foreignId('gambar_og_id')->nullable()->constrained('berkas_media')->nullOnDelete();
            $table->enum('jenis_identitas_penerbit', ['orang', 'organisasi'])->default('orang');
            $table->string('kode_verifikasi_google', 120)->nullable();
            $table->string('kode_verifikasi_bing', 120)->nullable();
            $table->boolean('izinkan_unduh')->default(true);
            $table->unsignedSmallInteger('batas_unggah_mb')->default(25);
            $table->text('panduan_penamaan_versi')->nullable();
            $table->boolean('statistik_aktif')->default(true);
            $table->boolean('kecualikan_admin')->default(true);
            $table->boolean('kecualikan_bot')->default(true);
            $table->unsignedSmallInteger('retensi_statistik_hari')->default(365);
            $table->unsignedSmallInteger('retensi_pesan_hari')->default(730);
            $table->foreignId('diperbarui_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->stempelWaktu();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_website');
    }
};
