<?php

namespace Database\Seeders;

use App\Models\AspekKarakter;
use App\Models\IndikatorKarakter;
use Illuminate\Database\Seeder;

/** 16 indikator (4 per aspek) sebagai data referensi materi, tanpa data anak/skor. */
class IndikatorKarakterSeeder extends Seeder
{
    public function run(): void
    {
        foreach (MateriSumber::data()['aspek'] as $a) {
            $aspek = AspekKarakter::query()->where('kode_aspek', $a['kode_aspek'])->first();
            foreach ($a['indikator'] as $i) {
                if (! $aspek || IndikatorKarakter::query()->where('nomor_indikator', $i['nomor_indikator'])->exists()) {
                    continue;
                }
                $ind = new IndikatorKarakter;
                $ind->forceFill([...$i, 'aspek_id' => $aspek->id, 'urutan' => $i['nomor_indikator'], 'aktif' => true])->save();
            }
        }
    }
}
