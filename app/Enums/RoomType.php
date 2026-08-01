<?php

namespace App\Enums;

enum RoomType: string
{
    case SINGLE = 'single';
    case DOUBLE = 'double';
    case TWIN = 'twin';
    case DELUXE = 'deluxe';
    case SUITE = 'suite';
}
