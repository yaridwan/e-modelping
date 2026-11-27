<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subbab_modul', function (Blueprint $table) {
            $table->id();
            // Bab inti tidak pernah dihapus; FK tanpa aksi kaskade agar penghapusan tidak sengaja ditolak.
            $table->foreignId('bab_id')->constrained('bab_modul');
            $table->string('kode_subbab', 20);
            $table->string('judul', 191);
            $table->string('slug', 191);
            $table->text('ringkasan')->nullable();
            $table->longText('isi');
            $table->longText('contoh_praktik')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('berkas_media')->nullOnDelete();
            $table->unsignedSmallInteger('urutan');
            $table->enum('status', ['draf', 'terbit', 'arsip'])->default('draf')->index();
            $table->json('metadata_sumber')->nullable();
            $table->auditPengguna();
            $table->stempelWaktu();

            $table->unique(['bab_id', 'slug']);
            $table->unique(['bab_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subbab_modul');
    }
};
