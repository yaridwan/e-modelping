<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_navigasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('induk_id')->nullable()->constrained('menu_navigasi')->cascadeOnDelete();
            $table->enum('lokasi', ['utama', 'footer'])->index();
            $table->string('label', 80);
            // Tepat satu target sesuai jenis (divalidasi di Form Request dan model):
            // halaman -> halaman_id, bab -> bab_id, internal/eksternal -> url.
            $table->enum('jenis_tautan', ['halaman', 'bab', 'internal', 'eksternal']);
            $table->foreignId('halaman_id')->nullable()->constrained('halaman_informasi')->cascadeOnDelete();
            $table->foreignId('bab_id')->nullable()->constrained('bab_modul');
            $table->string('url', 255)->nullable();
            $table->string('ikon', 40)->nullable();
            $table->boolean('buka_tab_baru')->default(false);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->stempelWaktu();

            $table->index(['lokasi', 'aktif', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_navigasi');
    }
};
