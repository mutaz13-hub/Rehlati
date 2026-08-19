<?php

namespace Tests\Unit;

use App\Enums\TripStatus;
use PHPUnit\Framework\TestCase;

class TripStatusTest extends TestCase
{
    public function test_all_statuses_are_string_backed(): void
    {
        foreach (TripStatus::cases() as $case) {
            $this->assertIsString($case->value);
        }
    }

    public function test_preparing_can_only_transition_to_prepared(): void
    {
        $this->assertSame([TripStatus::PREPARED], TripStatus::PREPARING->allowedTransitions());
    }

    public function test_active_can_only_transition_to_finished(): void
    {
        $this->assertSame([TripStatus::FINISHED], TripStatus::ACTIVE->allowedTransitions());
    }

    public function test_finished_has_no_allowed_transitions(): void
    {
        $this->assertSame([], TripStatus::FINISHED->allowedTransitions());
    }

    public function test_values_returns_all_string_values(): void
    {
        $values = TripStatus::values();

        $this->assertContains('preparing', $values);
        $this->assertContains('active', $values);
        $this->assertCount(4, $values);
    }
}
