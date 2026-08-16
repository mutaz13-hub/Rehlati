<?php

namespace App\Enums;

enum CommunityVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
