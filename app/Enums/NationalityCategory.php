<?php

namespace App\Enums;

enum NationalityCategory: string
{
    case SYRIAN = 'syrian';
    case EXPAT = 'expat';
    case FOREIGNER = 'foreigner';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
