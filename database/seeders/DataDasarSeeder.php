<?php

namespace Database\Seeders;

use App\Layanan\LayananPengaturan;
use Illuminate\Database\Seeder;

class DataDasarSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RiwayatMigrasiSeeder::class,
            PenggunaSeeder::class,
            PengaturanWebsiteSeeder::class,
            BerkasMediaSeeder::class,
            BabModulSeeder::class,
            SubbabModulSeeder::class,
            FasePingSeeder::class,
            LangkahFaseSeeder::class,
            AspekKarakterSeeder::class,
            IndikatorKarakterSeeder::class,
            HalamanInformasiSeeder::class,
            MenuNavigasiSeeder::class,
            TautanSosialSeeder::class,
            BagianBerandaSeeder::class,
            PertanyaanUmumSeeder::class,
            ReferensiPustakaSeeder::class,
            VersiDokumenSeeder::class,
            GaleriKegiatanSeeder::class,
            PesanKontakSeeder::class,
            KunjunganHalamanSeeder::class,
            AktivitasDokumenSeeder::class,
            LogAktivitasSeeder::class,
            PengalihanUrlSeeder::class,
            TokenAturUlangSandiSeeder::class,
        ]);

        app(LayananPengaturan::class)->bersihkan();
    }
}
