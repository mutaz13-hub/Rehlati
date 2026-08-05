<?php

namespace App\Enums;

enum RoomClass: string
{
    case STANDARD = 'standard';
    case DELUXE = 'deluxe';
    case SUPERIOR = 'superior';
    case SUITE = 'suite';
}
