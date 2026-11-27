<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berkas_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('judul', 191);
            $table->enum('jenis_media', ['gambar', 'pdf', 'video_eksternal'])->index();
            $table->string('nama_asli', 255)->nullable();
            $table->string('nama_simpan', 120)->nullable();
            $table->string('lokasi_penyimpanan', 30)->nullable();
            $table->string('jalur_berkas', 255)->nullable();
            $table->string('url_eksternal', 500)->nullable();
            $table->string('tipe_mime', 100)->nullable();
            $table->string('ekstensi', 10)->nullable();
            $table->unsignedBigInteger('ukuran_bita')->default(0);
            $table->unsignedInteger('lebar_piksel')->nullable();
            $table->unsignedInteger('tinggi_piksel')->nullable();
            $table->unsignedInteger('jumlah_halaman')->nullable();
            $table->string('teks_alternatif', 255)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('sumber', 255)->nullable();
            $table->string('lisensi', 120)->nullable();
            $table->char('checksum_sha256', 64)->nullable()->index();
            $table->foreignId('diunggah_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->stempelWaktu();
        });

        // Konsistensi jenis: berkas lokal wajib punya jalur, video eksternal wajib punya URL.
        DB::statement("ALTER TABLE berkas_media ADD CONSTRAINT chk_berkas_media_jenis CHECK (
            (jenis_media = 'video_eksternal' AND url_eksternal IS NOT NULL AND jalur_berkas IS NULL)
            OR (jenis_media IN ('gambar','pdf') AND jalur_berkas IS NOT NULL AND url_eksternal IS NULL)
        )");

        Schema::table('pengguna', function (Blueprint $table) {
            $table->foreign('foto_profil_id')->references('id')->on('berkas_media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropForeign(['foto_profil_id']);
        });
        Schema::dropIfExists('berkas_media');
    }
};
