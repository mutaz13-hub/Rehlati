<?php

namespace App\Enums;

enum MediaType: string
{
    case VIDEO = 'video';
    case PICTURE = 'picture';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
