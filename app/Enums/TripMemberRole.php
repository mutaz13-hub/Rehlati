<?php

namespace App\Enums;

enum TripMemberRole: string
{
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
