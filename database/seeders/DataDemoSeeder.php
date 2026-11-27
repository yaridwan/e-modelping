<?php

namespace Database\Seeders;

use App\Enums\StatusPesan;
use App\Models\AktivitasDokumen;
use App\Models\BabModul;
use App\Models\KunjunganHalaman;
use App\Models\LogAktivitas;
use App\Models\PengalihanUrl;
use App\Models\PesanKontak;
use App\Models\VersiDokumen;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * DATA DEMONSTRASI deterministik (seed acak tetap) untuk lokal/pengujian. Seluruh baris
 * aktivitas/pesan/log ditandai data_contoh = true dan dipisahkan dari statistik nyata.
 * Menolak berjalan di produksi.
 */
class DataDemoSeeder extends Seeder
{
    public const PENANDA_PENGALIHAN = '/contoh-tautan-lama-demo';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DataDemoSeeder ditolak di lingkungan produksi.');
        }
        if (KunjunganHalaman::query()->where('data_contoh', true)->exists()) {
            $this->command?->info('Data demo sudah ada. Gunakan `php artisan ping:reset-demo` untuk membuat ulang.');

            return;
        }

        mt_srand(20261126);
        $tz = 'Asia/Jakarta';
        $bab = BabModul::query()->orderBy('urutan')->get(['id', 'slug']);
        $versi = VersiDokumen::query()->where('versi_aktif', true)->pluck('id')->all();
        $perangkat = ['desktop', 'ponsel', 'ponsel', 'tablet'];
        $hariIni = now()->timezone($tz)->startOfDay();
        $kunjungan = [];
        $aktivitas = [];

        for ($h = 89; $h >= 0; $h--) {
            $tanggal = $hariIni->subDays($h);
            $dasar = 6 + (int) (4 * sin($h / 6)) + ($tanggal->isWeekend() ? -3 : 3);
            $jumlahPengunjung = max(2, $dasar + mt_rand(0, 6));
            for ($p = 0; $p < $jumlahPengunjung; $p++) {
                $sidik = hash('sha256', "demo-{$h}-{$p}-".mt_rand());
                $jalur = ['/', '/modul'];
                $babDibuka = $bab->random(mt_rand(1, 3));
                foreach ($babDibuka as $b) {
                    $jalur[] = '/modul/'.$b->slug;
                }
                foreach (array_unique($jalur) as $j) {
                    $b = str_starts_with($j, '/modul/') ? $bab->firstWhere('slug', substr($j, 7)) : null;
                    $kunjungan[] = [
                        'jenis_halaman' => $b ? 'bab' : ($j === '/' ? 'beranda' : 'daftar_bab'),
                        'bab_id' => $b?->id, 'halaman_id' => null, 'jalur_halaman' => $j,
                        'asal_domain' => mt_rand(0, 4) === 0 ? 'contoh-perujuk.test' : null,
                        'sidik_pengunjung' => $sidik, 'tanggal_kunjungan' => $tanggal->toDateString(),
                        'jenis_perangkat' => $perangkat[mt_rand(0, 3)], 'data_contoh' => true,
                        'dibuat_pada' => $tanggal->addHours(mt_rand(7, 20))->utc(),
                    ];
                }
                if ($versi && mt_rand(0, 2) === 0) {
                    $v = $versi[array_rand($versi)];
                    $aktivitas[] = ['versi_dokumen_id' => $v, 'jenis_aktivitas' => 'baca', 'sidik_pengunjung' => $sidik, 'jenis_perangkat' => $perangkat[mt_rand(0, 3)], 'tanggal_aktivitas' => $tanggal->toDateString(), 'data_contoh' => true, 'dibuat_pada' => $tanggal->addHours(10)->utc()];
                    if (mt_rand(0, 2) === 0) {
                        $aktivitas[] = ['versi_dokumen_id' => $v, 'jenis_aktivitas' => 'unduh', 'sidik_pengunjung' => $sidik, 'jenis_perangkat' => $perangkat[mt_rand(0, 3)], 'tanggal_aktivitas' => $tanggal->toDateString(), 'data_contoh' => true, 'dibuat_pada' => $tanggal->addHours(11)->utc()];
                    }
                }
            }
        }
        foreach (array_chunk($kunjungan, 500) as $potongan) {
            KunjunganHalaman::query()->insertOrIgnore($potongan);
        }
        foreach (array_chunk($aktivitas, 500) as $potongan) {
            AktivitasDokumen::query()->insertOrIgnore($potongan);
        }

        $pesan = [
            ['Guru Contoh A', 'guru.a@contoh.test', 'TK Contoh Melati', 'Pertanyaan durasi Inter-Class Transition', 'Apakah durasi 2–5 menit berlaku untuk setiap perpindahan kegiatan? (pesan demonstrasi)', StatusPesan::Baru],
            ['Guru Contoh B', 'guru.b@contoh.test', null, 'Usulan contoh dialog tambahan', 'Mohon tambahan contoh dialog untuk anak yang pemalu. (pesan demonstrasi)', StatusPesan::Dibaca],
            ['Pengunjung Contoh', 'pengunjung@contoh.test', 'KB Contoh Mawar', 'Koreksi penulisan', 'Ada salah ketik pada salah satu subbab. (pesan demonstrasi)', StatusPesan::Ditindaklanjuti],
            ['Kepala Sekolah Contoh', 'kepsek@contoh.test', 'PAUD Contoh', 'Izin memakai materi pelatihan', 'Bolehkah materi ini dipakai pada pelatihan internal guru? (pesan demonstrasi)', StatusPesan::Arsip],
            ['Guru Contoh C', 'guru.c@contoh.test', null, 'Lembar observasi', 'Apakah lembar observasi tersedia dalam format cetak? (pesan demonstrasi)', StatusPesan::Baru],
        ];
        foreach ($pesan as $i => [$nama, $surel, $inst, $subjek, $isi, $status]) {
            $m = new PesanKontak;
            $m->forceFill([
                'nama_pengirim' => $nama, 'surel_pengirim' => $surel, 'institusi' => $inst, 'subjek' => $subjek, 'isi_pesan' => $isi,
                'persetujuan_privasi' => true, 'status' => $status, 'data_contoh' => true,
                'dibaca_pada' => $status !== StatusPesan::Baru ? now()->subDays(5 - $i) : null,
                'ditindaklanjuti_pada' => $status === StatusPesan::Ditindaklanjuti ? now()->subDays(2) : null,
                'dibuat_pada' => now()->subDays(10 - $i * 2), 'diperbarui_pada' => now()->subDays(10 - $i * 2),
            ])->save();
        }

        foreach ([['terbitkan', 'bab_modul', 'Terbitkan Bab I Pendahuluan (demo)'], ['perbarui', 'subbab_modul', 'Perbarui subbab Latar Belakang (demo)'], ['unggah_dokumen', 'versi_dokumen', 'Unggah draf PDF Bab IV v1.1 (demo)'], ['perbarui_pengaturan', 'pengaturan_website', 'Perbarui pengaturan tampilan (demo)'], ['masuk', 'pengguna', 'Admin masuk (demo)']] as $i => [$aksi, $entitas, $ringkasan]) {
            LogAktivitas::query()->create(['aksi' => $aksi, 'jenis_entitas' => $entitas, 'ringkasan' => $ringkasan, 'data_contoh' => true, 'dibuat_pada' => now()->subDays(6 - $i)]);
        }

        PengalihanUrl::query()->firstOrCreate(['jalur_lama' => self::PENANDA_PENGALIHAN], ['jalur_baru' => '/modul', 'kode_status' => 301, 'aktif' => true]);

        $this->command?->info('Data demo dibuat: '.count($kunjungan).' kunjungan, '.count($aktivitas).' aktivitas dokumen, '.count($pesan).' pesan, 5 log (semua data_contoh = true).');
    }
}
