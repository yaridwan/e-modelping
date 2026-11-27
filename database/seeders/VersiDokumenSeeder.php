<?php

namespace Database\Seeders;

use App\Enums\StatusKonten;
use App\Layanan\LayananMedia;
use App\Models\BabModul;
use App\Models\VersiDokumen;
use App\Support\PembuatPdfSederhana;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Impor PDF sumber lokal (database/seeders/aset/sumber, dipisah dengan scripts/sumber/pisah_pdf.py)
 * sebagai versi 1.0 aktif. Integritas diperiksa dengan SHA-256 dan jumlah halaman manifest.
 * Bila aset tidak ada/tidak cocok, dibuat PDF demonstrasi valid berlabel jelas.
 * Hanya berjalan untuk lingkup yang belum memiliki versi (tidak menimpa unggahan pemilik).
 */
class VersiDokumenSeeder extends Seeder
{
    public function run(): void
    {
        $media = app(LayananMedia::class);
        $dir = database_path('seeders/aset/sumber');
        $manifest = is_file($dir.'/manifest.json') ? json_decode((string) file_get_contents($dir.'/manifest.json'), true) : null;

        foreach ($manifest['dokumen'] ?? $this->dokumenDemo() as $d) {
            $bab = $d['jenis'] === 'bab' ? BabModul::query()->where('nomor_bab', $d['nomor_bab'])->first() : null;
            if ($d['jenis'] === 'bab' && ! $bab) {
                continue;
            }
            $ada = VersiDokumen::query()->where('jenis_dokumen', $d['jenis'])
                ->when($bab, fn ($q) => $q->where('bab_id', $bab->id), fn ($q) => $q->whereNull('bab_id'))->exists();
            if ($ada) {
                continue;
            }

            $jalur = $dir.'/'.($d['berkas'] ?? '');
            $asli = $manifest && is_file($jalur) && hash_file('sha256', $jalur) === $d['sha256'];
            if (! $asli) {
                $jalur = $this->buatDemo($d, $bab);
            }
            $judul = $d['jenis'] === 'bab' ? "Bab {$bab->nomor_romawi} {$bab->judul}" : 'E-Model Pembiasaan PING (modul lengkap)';
            $berkas = $media->imporPdfLokal($jalur, $asli ? $judul : 'Dokumen demonstrasi — '.$judul, [
                'sumber' => $asli
                    ? 'PDF sumber “E-Model PING_Revisi 1.pdf”, halaman PDF '.$d['halaman_pdf_awal'].'–'.$d['halaman_pdf_akhir'].($d['catatan'] ? '. '.$d['catatan'] : '')
                    : 'Dibuat aplikasi sebagai dokumen demonstrasi (bukan PDF sumber).',
                'keterangan' => $asli ? 'Hasil pemisahan PDF sumber tanpa pemotongan di tengah halaman.' : 'Dokumen demonstrasi — ringkasan untuk pengujian aplikasi.',
                'lisensi' => $asli ? 'Hak cipta materi pada penyusun' : 'Demonstrasi',
            ]);
            if ($berkas->jumlah_halaman !== ($asli ? (int) $d['jumlah_halaman'] : $berkas->jumlah_halaman)) {
                $this->command?->error("Jumlah halaman {$d['berkas']} tidak sesuai manifest; dilewati.");

                continue;
            }

            DB::transaction(function () use ($d, $bab, $berkas, $judul, $asli) {
                $v = new VersiDokumen;
                $v->forceFill([
                    'jenis_dokumen' => $d['jenis'], 'bab_id' => $bab?->id, 'berkas_id' => $berkas->id,
                    'nomor_versi' => '1.0', 'judul_dokumen' => $asli ? $judul : 'Dokumen demonstrasi — '.$judul,
                    'catatan_perubahan' => $asli ? 'Versi awal dari PDF sumber (Revisi 1).' : 'Dokumen demonstrasi — ringkasan untuk pengujian aplikasi.',
                    'status' => StatusKonten::Terbit, 'versi_aktif' => true, 'izinkan_unduh' => true, 'diterbitkan_pada' => now(),
                ])->save();
            });
        }
    }

    private function dokumenDemo(): array
    {
        $daftar = [['jenis' => 'modul_lengkap', 'berkas' => null, 'sha256' => null, 'halaman_pdf_awal' => null, 'halaman_pdf_akhir' => null, 'catatan' => null]];
        foreach (range(1, 9) as $n) {
            $daftar[] = ['jenis' => 'bab', 'nomor_bab' => $n, 'berkas' => null, 'sha256' => null, 'halaman_pdf_awal' => null, 'halaman_pdf_akhir' => null, 'catatan' => null];
        }

        return $daftar;
    }

    private function buatDemo(array $d, ?BabModul $bab): string
    {
        $judul = $bab ? "Bab {$bab->nomor_romawi}: {$bab->judul}" : 'E-Model PING (modul lengkap)';
        $halaman = [[
            'Dokumen ini dibuat otomatis oleh aplikasi karena PDF sumber tidak tersedia di server.',
            'Isi berikut adalah ringkasan untuk menguji fitur baca, unduh, dan versi dokumen.',
            $bab?->ringkasan ?? 'Model Pembiasaan PING terdiri atas tiga fase: Pre-Class Greeting, Inter-Class Transition Greeting, dan End-Day Closing Greeting.',
        ]];
        foreach (($bab?->subbab()->get() ?? collect()) as $s) {
            $halaman[] = [$s->kode_subbab.' '.$s->judul, (string) $s->ringkasan];
        }
        if (count($halaman) === 1) {
            $halaman[] = ['Empat aspek karakter: disiplin, tanggung jawab, kontrol diri, dan sopan santun (16 indikator).'];
        }
        $jalur = storage_path('app/private/sementara-'.bin2hex(random_bytes(6)).'.pdf');
        @mkdir(dirname($jalur), 0775, true);
        file_put_contents($jalur, PembuatPdfSederhana::buat($judul, $halaman));
        register_shutdown_function(fn () => @unlink($jalur));

        return $jalur;
    }
}
