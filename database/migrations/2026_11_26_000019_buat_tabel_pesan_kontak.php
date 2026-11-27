<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesan_kontak', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pengirim', 120);
            $table->string('surel_pengirim', 191);
            $table->string('institusi', 150)->nullable();
            $table->string('subjek', 150);
            $table->text('isi_pesan');
            $table->boolean('persetujuan_privasi')->default(false);
            $table->enum('status', ['baru', 'dibaca', 'ditindaklanjuti', 'arsip'])->default('baru');
            $table->text('catatan_admin')->nullable();
            $table->dateTime('dibaca_pada')->nullable();
            $table->dateTime('ditindaklanjuti_pada')->nullable();
            $table->char('sidik_pengirim', 64)->nullable();
            $table->boolean('data_contoh')->default(false);
            $table->stempelWaktu();

            $table->index(['status', 'dibuat_pada']);
            $table->index(['data_contoh', 'dibuat_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesan_kontak');
    }
};
