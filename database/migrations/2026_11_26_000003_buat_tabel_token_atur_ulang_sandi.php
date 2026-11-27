<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_atur_ulang_sandi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->constrained('pengguna')->cascadeOnDelete();
            // Hanya hash SHA-256 (HMAC) yang disimpan; token mentah hanya dikirim lewat surel.
            $table->char('token_hash', 64)->unique();
            $table->dateTime('kedaluwarsa_pada')->index();
            $table->dateTime('digunakan_pada')->nullable();
            $table->dateTime('dibuat_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_atur_ulang_sandi');
    }
};
