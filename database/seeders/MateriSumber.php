<?php

namespace Database\Seeders;

/** Pembaca data kurasi PDF sumber (database/seeders/data/materi-sumber.json). */
class MateriSumber
{
    private static ?array $data = null;

    public static function data(): array
    {
        return self::$data ??= json_decode((string) file_get_contents(__DIR__.'/data/materi-sumber.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function bab(int $nomor): array
    {
        return collect(self::data()['bab'])->firstWhere('nomor_bab', $nomor);
    }

    public static function editorial(): array
    {
        return require __DIR__.'/data/bab-editorial.php';
    }
}
