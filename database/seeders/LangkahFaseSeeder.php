<?php

namespace Database\Seeders;

use App\Models\FasePing;
use App\Models\LangkahFase;
use Illuminate\Database\Seeder;

/** 17 langkah fase (6 + 6 + 5) sesuai sintaks pada Bab IV sumber. */
class LangkahFaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (MateriSumber::data()['fase'] as $f) {
            $fase = FasePing::query()->where('kode_fase', $f['kode_fase'])->first();
            if (! $fase || $fase->langkah()->exists()) {
                continue;
            }
            foreach ($f['langkah'] as $l) {
                $langkah = new LangkahFase;
                $langkah->forceFill([
                    'fase_id' => $fase->id,
                    'nomor_langkah' => $l['nomor_langkah'],
                    'judul' => $l['judul'],
                    'penjelasan' => $l['penjelasan'],
                    'aktivitas_guru' => $l['aktivitas_guru'],
                    'aktivitas_anak' => $l['aktivitas_anak'],
                    'contoh_dialog' => $l['contoh_dialog'],
                    'urutan' => $l['nomor_langkah'],
                ])->save();
            }
        }
    }
}
