<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris = satu peristiwa baca/unduh yang lolos deduplikasi. Permintaan potongan byte
        // (HTTP Range selain awal berkas) tidak dicatat sebagai pembacaan baru.
        Schema::create('aktivitas_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('versi_dokumen_id')->constrained('versi_dokumen');
            $table->enum('jenis_aktivitas', ['baca', 'unduh']);
            $table->char('sidik_pengunjung', 64);
            $table->enum('jenis_perangkat', ['desktop', 'tablet', 'ponsel', 'lainnya'])->default('lainnya');
            $table->date('tanggal_aktivitas');
            $table->boolean('data_contoh')->default(false);
            $table->dateTime('dibuat_pada');

            $table->unique(['versi_dokumen_id', 'jenis_aktivitas', 'sidik_pengunjung', 'tanggal_aktivitas'], 'aktivitas_dokumen_dedup_unique');
            $table->index(['data_contoh', 'tanggal_aktivitas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktivitas_dokumen');
    }
};
