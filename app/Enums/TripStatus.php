<?php

namespace App\Enums;

enum TripStatus: string
{
    case PREPARING = 'preparing';
    case PREPARED = 'prepared';
    case ACTIVE = 'active';
    case FINISHED = 'finished';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Statuses a user is allowed to move the trip to manually.
     */
    public static function transitionableTargets(): array
    {
        return [self::PREPARED->value, self::ACTIVE->value, self::FINISHED->value];
    }

    /**
     * Valid destination statuses reachable from the current one.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PREPARING => [self::PREPARED],
            self::PREPARED => [self::ACTIVE],
            self::ACTIVE => [self::FINISHED],
            self::FINISHED => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PREPARING => __('Preparing'),
            self::PREPARED => __('Prepared'),
            self::ACTIVE => __('Active'),
            self::FINISHED => __('Finished'),
        };
    }
}
