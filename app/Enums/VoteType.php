<?php

namespace App\Enums;

enum VoteType: string
{
    case UP = 'up';
    case DOWN = 'down';

    public function label(): string
    {
        return match ($this) {
            self::UP => 'Up Vote',
            self::DOWN => 'Down Vote',
        };
    }
}
