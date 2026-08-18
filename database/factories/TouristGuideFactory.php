<?php

namespace Database\Factories;

use App\Models\TouristGuide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TouristGuide>
 */
class TouristGuideFactory extends Factory
{
    protected $model = TouristGuide::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->e164PhoneNumber(),
            'availability' => fake()->randomElements(
                ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                fake()->numberBetween(2, 5),
            ),
            'is_active' => true,
        ];
    }
}
