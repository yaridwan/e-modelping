<?php

namespace App\Enums;

enum JenisMedia: string
{
    case Gambar = 'gambar';
    case Pdf = 'pdf';
    case VideoEksternal = 'video_eksternal';

    public function label(): string
    {
        return match ($this) {
            self::Gambar => 'Gambar',
            self::Pdf => 'PDF',
            self::VideoEksternal => 'Video eksternal',
        };
    }
}
