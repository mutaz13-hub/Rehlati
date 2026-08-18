<?php

namespace Database\Factories;

use App\Enums\GuideRequestStatus;
use App\Models\GuideRequest;
use App\Models\TouristGuide;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuideRequest>
 */
class GuideRequestFactory extends Factory
{
    protected $model = GuideRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'tourist_guide_id' => TouristGuide::factory(),
            'status' => GuideRequestStatus::PENDING->value,
            'note' => fake()->sentence(),
        ];
    }
}
