<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fase_ping', function (Blueprint $table) {
            $table->id();
            $table->string('kode_fase', 10)->unique();
            $table->string('nama', 120);
            $table->string('slug', 120)->unique();
            $table->string('judul_indonesia', 150);
            $table->text('deskripsi');
            $table->json('tujuan')->nullable();
            $table->text('waktu_pelaksanaan')->nullable();
            $table->unsignedSmallInteger('durasi_minimum_menit')->nullable();
            $table->unsignedSmallInteger('durasi_maksimum_menit')->nullable();
            $table->string('frekuensi_panduan', 191)->nullable();
            $table->foreignId('media_id')->nullable()->constrained('berkas_media')->nullOnDelete();
            $table->text('catatan_guru')->nullable();
            $table->json('indikator_keberhasilan')->nullable();
            // Daftar {aspek, bentuk}: bentuk penguatan tiap aspek karakter pada fase ini.
            $table->json('penguatan_karakter')->nullable();
            $table->foreignId('bab_id')->nullable()->constrained('bab_modul');
            $table->unsignedSmallInteger('urutan')->unique();
            $table->enum('status', ['draf', 'terbit', 'arsip'])->default('terbit')->index();
            $table->stempelWaktu();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fase_ping');
    }
};
