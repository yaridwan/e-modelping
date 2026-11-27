<?php

namespace App\Enums;

enum StatusPesan: string
{
    case Baru = 'baru';
    case Dibaca = 'dibaca';
    case Ditindaklanjuti = 'ditindaklanjuti';
    case Arsip = 'arsip';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Dibaca => 'Dibaca',
            self::Ditindaklanjuti => 'Ditindaklanjuti',
            self::Arsip => 'Arsip',
        };
    }

    /** @return list<string> */
    public static function nilai(): array
    {
        return array_column(self::cases(), 'value');
    }
}
