<?php

namespace App\Support;

/**
 * Pembuat PDF teks sederhana (A4, Helvetica) tanpa dependensi eksternal.
 * Dipakai seeder sebagai cadangan ketika PDF sumber tidak tersedia, sehingga setiap tombol
 * dokumen tetap membuka berkas PDF yang valid berlabel "Dokumen demonstrasi".
 */
class PembuatPdfSederhana
{
    /** @param list<list<string>> $halaman daftar baris per halaman */
    public static function buat(string $judul, array $halaman): string
    {
        $objek = [];
        $objek[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objek[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objek[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $anak = [];
        $nomor = 5;
        foreach ($halaman as $indeks => $baris) {
            $isi = 'BT /F2 16 Tf 56 790 Td ('.self::esc($judul).") Tj ET\n";
            $isi .= 'BT /F1 9 Tf 56 772 Td ('.self::esc('Dokumen demonstrasi - ringkasan untuk pengujian aplikasi').") Tj ET\n";
            $y = 740;
            foreach ($baris as $b) {
                foreach (self::bungkus($b, 92) as $potongan) {
                    if ($y < 60) {
                        break 2;
                    }
                    $isi .= 'BT /F1 11 Tf 56 '.$y.' Td ('.self::esc($potongan).") Tj ET\n";
                    $y -= 16;
                }
                $y -= 4;
            }
            $isi .= 'BT /F1 9 Tf 290 30 Td ('.($indeks + 1).") Tj ET\n";
            $idIsi = $nomor++;
            $idHalaman = $nomor++;
            $objek[$idIsi] = '<< /Length '.strlen($isi)." >>\nstream\n".$isi.'endstream';
            $objek[$idHalaman] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$idIsi.' 0 R >>';
            $anak[] = $idHalaman.' 0 R';
        }
        $objek[2] = '<< /Type /Pages /Kids ['.implode(' ', $anak).'] /Count '.count($anak).' >>';
        $objek[$nomor] = '<< /Title ('.self::esc($judul).') /Producer (E-Model PING) >>';
        $info = $nomor;
        ksort($objek);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offset = [];
        foreach ($objek as $id => $isi) {
            $offset[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$isi."\nendobj\n";
        }
        $xref = strlen($pdf);
        $jumlah = max(array_keys($objek)) + 1;
        $pdf .= "xref\n0 {$jumlah}\n0000000000 65535 f \n";
        for ($i = 1; $i < $jumlah; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offset[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size {$jumlah} /Root 1 0 R /Info {$info} 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }

    private static function esc(string $teks): string
    {
        $teks = str_replace(['–', '—', '“', '”', '‘', '’', '≥', '≤', '×'], ['-', '-', '"', '"', "'", "'", '>=', '<=', 'x'], $teks);
        $teks = (string) iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $teks);

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $teks);
    }

    /** @return list<string> */
    private static function bungkus(string $teks, int $lebar): array
    {
        return explode("\n", wordwrap($teks, $lebar, "\n", true));
    }
}
