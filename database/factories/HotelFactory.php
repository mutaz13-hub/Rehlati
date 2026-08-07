<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hotel>
 */
class HotelFactory extends Factory
{
    protected $model = Hotel::class;

    public function definition(): array
    {
        return [
            'name_en' => fake()->unique()->company(),
            'name_ar' => fake()->unique()->company(),
            'city_id' => City::factory(),
            'stars' => fake()->randomFloat(2, 1, 5),
        ];
    }
}
