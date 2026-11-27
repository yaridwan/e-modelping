<?php

namespace Database\Seeders;

use App\Enums\StatusKonten;
use App\Models\BabModul;
use App\Models\FasePing;
use Illuminate\Database\Seeder;

class FasePingSeeder extends Seeder
{
    public function run(): void
    {
        $babSintaks = BabModul::query()->where('nomor_bab', 4)->value('id');
        foreach (MateriSumber::data()['fase'] as $i => $f) {
            if (FasePing::query()->where('kode_fase', $f['kode_fase'])->exists()) {
                continue;
            }
            $fase = new FasePing;
            $fase->forceFill([
                'kode_fase' => $f['kode_fase'],
                'nama' => $f['nama'],
                'slug' => $f['slug'],
                'judul_indonesia' => $f['judul_indonesia'],
                'deskripsi' => $f['deskripsi'],
                'tujuan' => $f['tujuan'],
                'waktu_pelaksanaan' => $f['waktu_pelaksanaan'],
                'durasi_minimum_menit' => $f['durasi_minimum_menit'],
                'durasi_maksimum_menit' => $f['durasi_maksimum_menit'],
                'frekuensi_panduan' => $f['frekuensi_panduan'],
                'catatan_guru' => $f['catatan_guru'],
                'indikator_keberhasilan' => $f['indikator_keberhasilan'],
                'penguatan_karakter' => collect($f['penguatan'])->map(fn ($bentuk, $aspek) => ['aspek' => $aspek, 'bentuk' => $bentuk])->values()->all(),
                'bab_id' => $babSintaks,
                'urutan' => $i + 1,
                'status' => StatusKonten::Terbit,
            ])->save();
        }
    }
}
