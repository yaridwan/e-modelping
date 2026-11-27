<?php

namespace App\Enums;

enum StatusKonten: string
{
    case Draf = 'draf';
    case Terbit = 'terbit';
    case Arsip = 'arsip';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Terbit => 'Terbit',
            self::Arsip => 'Arsip',
        };
    }

    /** @return list<string> */
    public static function nilai(): array
    {
        return array_column(self::cases(), 'value');
    }
}
