<?php

namespace App\Enums;

enum Weekday: string
{
    case SUNDAY = 'sunday';
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::SUNDAY => __('Sunday'),
            self::MONDAY => __('Monday'),
            self::TUESDAY => __('Tuesday'),
            self::WEDNESDAY => __('Wednesday'),
            self::THURSDAY => __('Thursday'),
            self::FRIDAY => __('Friday'),
            self::SATURDAY => __('Saturday'),
        };
    }
}
