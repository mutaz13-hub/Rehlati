<?php

namespace App\Enums;

enum RoomLayout: string
{
    case SINGLE = 'single';
    case DOUBLE = 'double';
    case TWIN = 'twin';
    case TRIPLE = 'triple';
    case FAMILY = 'family';
}
