<?php

namespace App\Enums;

enum BedType: string
{
    case SINGLE = 'single';
    case DOUBLE = 'double';
    case TWIN = 'twin';
    case KING = 'king';
    case QUEEN = 'queen';
}
