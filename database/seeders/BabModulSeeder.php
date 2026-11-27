<?php

namespace Database\Seeders;

use App\Enums\StatusKonten;
use App\Layanan\LayananSanitasiHtml;
use App\Models\BabModul;
use Illuminate\Database\Seeder;

/** Sembilan bab inti (nomor 1–9). Dibuat bila belum ada; suntingan pemilik tidak ditimpa. */
class BabModulSeeder extends Seeder
{
    public function run(): void
    {
        $sanitasi = app(LayananSanitasiHtml::class);
        $editorial = MateriSumber::editorial();

        foreach (MateriSumber::data()['bab'] as $sumber) {
            $nomor = (int) $sumber['nomor_bab'];
            if (BabModul::query()->where('nomor_bab', $nomor)->exists()) {
                continue;
            }
            $e = $editorial[$nomor];
            $bab = new BabModul;
            $bab->forceFill([
                'nomor_bab' => $nomor,
                'nomor_romawi' => $sumber['nomor_romawi'],
                'judul' => $sumber['judul'],
                'slug' => $e['slug'],
                'ringkasan' => $e['ringkasan'],
                'pengantar' => $sanitasi->bersihkan($sumber['pengantar_html'] ?: null),
                'tujuan_pembelajaran' => $e['tujuan'],
                'poin_penting' => $e['poin'],
                'urutan' => $nomor,
                'status' => StatusKonten::Terbit,
                'diterbitkan_pada' => now(),
                'judul_seo' => "Bab {$sumber['nomor_romawi']}: {$sumber['judul']} — Model Pembiasaan PING",
                'deskripsi_seo' => $e['ringkasan'],
                'metadata_sumber' => [
                    'berkas' => MateriSumber::data()['sumber'],
                    'halaman_pdf' => [$sumber['halaman_pdf_awal'], $sumber['halaman_pdf_akhir']],
                    'halaman_cetak' => [$sumber['halaman_cetak_awal'], $sumber['halaman_cetak_akhir']],
                    'metode' => 'Ekstraksi teks pdftotext + kurasi (scripts/sumber).',
                ],
            ])->save();
        }
    }
}
