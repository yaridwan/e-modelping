<?php

namespace Database\Seeders;

use App\Enums\StatusKonten;
use App\Layanan\LayananSanitasiHtml;
use App\Models\BabModul;
use App\Models\SubbabModul;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Subbab dari PDF sumber, dengan provenance halaman pada metadata_sumber. */
class SubbabModulSeeder extends Seeder
{
    public function run(): void
    {
        $sanitasi = app(LayananSanitasiHtml::class);

        foreach (MateriSumber::data()['bab'] as $sumber) {
            $bab = BabModul::query()->where('nomor_bab', $sumber['nomor_bab'])->first();
            if (! $bab || $bab->subbab()->exists()) {
                continue; // jangan menimpa struktur subbab yang sudah dikelola pemilik
            }
            foreach ($sumber['subbab'] as $i => $s) {
                $isi = $sanitasi->bersihkan($s['html'] ?? '') ?? '<p>Materi subbab ini sedang disiapkan.</p>';
                $teks = LayananSanitasiHtml::teksPolos($isi);
                $kalimat = preg_split('/(?<=[.!?])\s+/u', $teks, 2)[0] ?? $teks;

                $sub = new SubbabModul;
                $sub->forceFill([
                    'bab_id' => $bab->id,
                    'kode_subbab' => $sumber['nomor_romawi'].'-'.$s['huruf'],
                    'judul' => $s['judul'],
                    'slug' => Str::slug($s['judul']),
                    'ringkasan' => Str::limit($kalimat, 280),
                    'isi' => $isi,
                    'contoh_praktik' => null,
                    'urutan' => $i + 1,
                    'status' => StatusKonten::Terbit,
                    'metadata_sumber' => [
                        'halaman_pdf' => [$s['halaman_pdf_awal'] ?? null, $s['halaman_pdf_akhir'] ?? null],
                        'halaman_cetak' => [$s['halaman_cetak_awal'] ?? null, $s['halaman_cetak_akhir'] ?? null],
                        'tabel' => array_column($s['tabel'] ?? [], 'nomor'),
                        'gambar' => array_column($s['gambar'] ?? [], 'nomor'),
                    ],
                ])->save();
            }
        }
    }
}
