<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aspek_karakter', function (Blueprint $table) {
            $table->id();
            $table->string('kode_aspek', 10)->unique();
            $table->string('nama', 80);
            $table->string('slug', 80)->unique();
            $table->text('deskripsi');
            $table->text('contoh_pembiasaan')->nullable();
            $table->string('fokus_pengamatan', 191)->nullable();
            $table->string('ikon', 40)->nullable();
            $table->string('warna', 20)->nullable();
            $table->unsignedSmallInteger('urutan')->unique();
            $table->boolean('aktif')->default(true);
            $table->stempelWaktu();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aspek_karakter');
    }
};
