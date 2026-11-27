<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referensi_pustaka', function (Blueprint $table) {
            $table->id();
            $table->string('penulis', 255);
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->string('judul', 500);
            $table->enum('jenis_sumber', ['buku', 'bab_buku', 'jurnal', 'laporan', 'laman', 'lainnya'])->default('lainnya');
            $table->string('nama_penerbit_jurnal', 255)->nullable();
            $table->string('volume', 20)->nullable();
            $table->string('nomor_terbitan', 20)->nullable();
            $table->string('halaman', 40)->nullable();
            $table->string('doi', 191)->nullable();
            $table->string('url', 500)->nullable();
            $table->date('tanggal_akses')->nullable();
            $table->text('catatan_editorial')->nullable();
            $table->enum('status_verifikasi', ['belum_diverifikasi', 'tercantum_di_sumber', 'terverifikasi', 'perlu_koreksi'])->default('belum_diverifikasi');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->stempelWaktu();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referensi_pustaka');
    }
};
