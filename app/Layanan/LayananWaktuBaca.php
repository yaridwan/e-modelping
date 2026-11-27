<?php

namespace App\Layanan;

/** Perkiraan waktu baca dihitung dari jumlah kata materi terbit (±200 kata per menit). */
class LayananWaktuBaca
{
    public const KATA_PER_MENIT = 200;

    public static function jumlahKata(string ...$html): int
    {
        $kata = 0;
        foreach ($html as $potongan) {
            $teks = LayananSanitasiHtml::teksPolos($potongan);
            $kata += $teks === '' ? 0 : count(preg_split('/\s+/u', $teks) ?: []);
        }

        return $kata;
    }

    public static function menit(int $jumlahKata): int
    {
        return max(1, (int) ceil($jumlahKata / self::KATA_PER_MENIT));
    }
}
