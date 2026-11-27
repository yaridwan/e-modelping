<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bab_modul', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('nomor_bab')->unique();
            $table->string('nomor_romawi', 6);
            $table->string('judul', 191);
            $table->string('slug', 191)->unique();
            $table->text('ringkasan')->nullable();
            $table->longText('pengantar')->nullable();
            $table->json('tujuan_pembelajaran')->nullable();
            $table->json('poin_penting')->nullable();
            $table->foreignId('gambar_sampul_id')->nullable()->constrained('berkas_media')->nullOnDelete();
            $table->unsignedSmallInteger('urutan')->unique();
            $table->enum('status', ['draf', 'terbit', 'arsip'])->default('draf')->index();
            $table->dateTime('diterbitkan_pada')->nullable();
            $table->string('judul_seo', 191)->nullable();
            $table->string('deskripsi_seo', 300)->nullable();
            $table->foreignId('gambar_og_id')->nullable()->constrained('berkas_media')->nullOnDelete();
            // Provenance internal: halaman sumber PDF, catatan pemetaan. Tidak dikirim ke publik.
            $table->json('metadata_sumber')->nullable();
            $table->auditPengguna();
            $table->stempelWaktu();
        });

        DB::statement('ALTER TABLE bab_modul ADD CONSTRAINT chk_bab_modul_nomor CHECK (nomor_bab BETWEEN 1 AND 9)');
    }

    public function down(): void
    {
        Schema::dropIfExists('bab_modul');
    }
};
