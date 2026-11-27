<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pertanyaan_umum', function (Blueprint $table) {
            $table->id();
            $table->string('kategori', 60)->default('Umum');
            $table->string('pertanyaan', 255);
            $table->text('jawaban');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true)->index();
            $table->stempelWaktu();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pertanyaan_umum');
    }
};
