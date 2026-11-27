<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengalihan_url', function (Blueprint $table) {
            $table->id();
            $table->string('jalur_lama', 191)->unique();
            $table->string('jalur_baru', 191);
            $table->unsignedSmallInteger('kode_status')->default(301);
            $table->boolean('aktif')->default(true);
            $table->stempelWaktu();
        });

        DB::statement('ALTER TABLE pengalihan_url ADD CONSTRAINT chk_pengalihan_kode CHECK (kode_status IN (301, 302, 308))');
    }

    public function down(): void
    {
        Schema::dropIfExists('pengalihan_url');
    }
};
