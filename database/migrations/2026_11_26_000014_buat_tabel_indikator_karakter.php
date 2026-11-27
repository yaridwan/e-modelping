<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Data referensi materi. Tidak ada relasi ke anak/siswa maupun penyimpanan skor.
        Schema::create('indikator_karakter', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aspek_id')->constrained('aspek_karakter');
            $table->unsignedTinyInteger('nomor_indikator')->unique();
            $table->string('rumusan', 255);
            $table->text('contoh_pengamatan')->nullable();
            $table->json('fase_pengamatan')->nullable();
            $table->text('catatan_pendampingan')->nullable();
            $table->text('rubrik_bb')->nullable();
            $table->text('rubrik_mb')->nullable();
            $table->text('rubrik_bsh')->nullable();
            $table->text('rubrik_bsb')->nullable();
            $table->unsignedSmallInteger('urutan');
            $table->boolean('aktif')->default(true);
            $table->stempelWaktu();
        });

        DB::statement('ALTER TABLE indikator_karakter ADD CONSTRAINT chk_indikator_nomor CHECK (nomor_indikator BETWEEN 1 AND 16)');
    }

    public function down(): void
    {
        Schema::dropIfExists('indikator_karakter');
    }
};
