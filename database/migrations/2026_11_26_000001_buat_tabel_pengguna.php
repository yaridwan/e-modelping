<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap', 150);
            $table->string('nama_panggilan', 60)->nullable();
            $table->string('surel', 191)->unique();
            $table->dateTime('surel_diverifikasi_pada')->nullable();
            $table->string('kata_sandi', 255);
            $table->string('token_pengingat', 100)->nullable();
            $table->string('nomor_telepon', 30)->nullable();
            $table->string('jabatan', 120)->nullable();
            $table->string('institusi', 150)->nullable();
            $table->string('bidang_keahlian', 191)->nullable();
            $table->text('biografi')->nullable();
            $table->unsignedBigInteger('foto_profil_id')->nullable()->index();
            $table->string('situs_pribadi', 255)->nullable();
            $table->string('surel_publik', 191)->nullable();
            $table->string('nomor_kontak_publik', 30)->nullable();
            $table->boolean('tampilkan_profil_publik')->default(false);
            $table->boolean('tampilkan_foto_publik')->default(false);
            $table->boolean('tampilkan_kontak_publik')->default(false);
            $table->enum('tema_pilihan', ['terang', 'gelap', 'sistem'])->default('sistem');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->boolean('wajib_ganti_sandi')->default(false);
            $table->dateTime('terakhir_masuk_pada')->nullable();
            $table->dateTime('sandi_diubah_pada')->nullable();
            $table->stempelWaktu();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengguna');
    }
};
