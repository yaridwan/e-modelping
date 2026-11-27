<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor CSV aman: sel yang diawali =, +, -, @, tab, atau CR diberi awalan apostrof
 * untuk mencegah formula injection saat dibuka di aplikasi spreadsheet.
 */
class EksporCsv
{
    public static function selAman(mixed $nilai): string
    {
        $teks = (string) ($nilai ?? '');
        if ($teks !== '' && in_array($teks[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$teks;
        }

        return $teks;
    }

    /**
     * @param  list<string>  $kepala
     * @param  iterable<array<int, mixed>>  $baris
     */
    public static function unduh(string $namaBerkas, array $kepala, iterable $baris): StreamedResponse
    {
        return response()->streamDownload(function () use ($kepala, $baris) {
            $keluar = fopen('php://output', 'w');
            fwrite($keluar, "\xEF\xBB\xBF");
            fputcsv($keluar, $kepala, ';', '"', '');
            foreach ($baris as $b) {
                fputcsv($keluar, array_map([self::class, 'selAman'], $b), ';', '"', '');
            }
            fclose($keluar);
        }, $namaBerkas, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
