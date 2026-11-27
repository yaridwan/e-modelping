<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('versi_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bab_id')->nullable()->constrained('bab_modul');
            $table->enum('jenis_dokumen', ['bab', 'modul_lengkap']);
            $table->foreignId('berkas_id')->constrained('berkas_media');
            $table->string('nomor_versi', 20);
            $table->string('judul_dokumen', 191);
            $table->text('catatan_perubahan')->nullable();
            $table->enum('status', ['draf', 'terbit', 'arsip'])->default('draf')->index();
            $table->boolean('versi_aktif')->default(false);
            $table->boolean('izinkan_unduh')->default(true);
            $table->dateTime('diterbitkan_pada')->nullable();
            $table->foreignId('diterbitkan_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->auditPengguna();
            $table->stempelWaktu();

            // Lingkup dokumen: "bab:<id>" atau "modul_lengkap:0".
            $table->string('lingkup_dokumen', 40)
                ->storedAs("CONCAT(jenis_dokumen, ':', COALESCE(bab_id, 0))");
            // Hanya terisi untuk versi aktif; UNIQUE menjamin satu versi aktif per lingkup
            // (NULL boleh berulang). Dilengkapi transaksi + row lock di LayananDokumen.
            $table->string('kunci_versi_aktif', 40)->nullable()
                ->storedAs("IF(versi_aktif = 1, CONCAT(jenis_dokumen, ':', COALESCE(bab_id, 0)), NULL)");

            $table->unique(['lingkup_dokumen', 'nomor_versi'], 'versi_dokumen_lingkup_nomor_unique');
            $table->unique('kunci_versi_aktif', 'versi_dokumen_satu_aktif_unique');
        });

        DB::statement("ALTER TABLE versi_dokumen ADD CONSTRAINT chk_versi_dokumen_lingkup CHECK (
            (jenis_dokumen = 'bab' AND bab_id IS NOT NULL) OR (jenis_dokumen = 'modul_lengkap' AND bab_id IS NULL)
        )");
        DB::statement("ALTER TABLE versi_dokumen ADD CONSTRAINT chk_versi_dokumen_aktif_terbit CHECK (versi_aktif = 0 OR status = 'terbit')");
    }

    public function down(): void
    {
        Schema::dropIfExists('versi_dokumen');
    }
};
