<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('langkah_fase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fase_id')->constrained('fase_ping')->cascadeOnDelete();
            $table->unsignedSmallInteger('nomor_langkah');
            $table->string('judul', 150);
            $table->text('penjelasan');
            $table->text('aktivitas_guru')->nullable();
            $table->text('aktivitas_anak')->nullable();
            // Daftar terstruktur [{pembicara, ucapan}].
            $table->json('contoh_dialog')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('berkas_media')->nullOnDelete();
            $table->unsignedSmallInteger('urutan');
            $table->stempelWaktu();

            $table->unique(['fase_id', 'nomor_langkah']);
            $table->unique(['fase_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('langkah_fase');
    }
};
