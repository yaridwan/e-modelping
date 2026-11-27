<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tautan_sosial', function (Blueprint $table) {
            $table->id();
            $table->string('nama_platform', 40);
            $table->string('label', 80);
            $table->string('url', 255)->nullable();
            $table->string('ikon', 40)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(false);
            $table->stempelWaktu();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tautan_sosial');
    }
};
