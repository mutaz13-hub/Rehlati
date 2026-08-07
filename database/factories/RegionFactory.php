<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    protected $model = Region::class;

    public function definition(): array
    {
        return [
            'name_en' => fake()->unique()->city(),
            'name_ar' => fake()->unique()->city(),
            'city_id' => City::factory(),
        ];
    }
}
