<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        $start_date = fake()->dateTimeBetween('now', '+1 year');

        return [
            'name_en' => fake()->unique()->words(3, true),
            'name_ar' => fake()->unique()->words(3, true),
            'start_date' => $start_date->format('Y-m-d'),
            'end_date' => $start_date->modify('+'.fake()->numberBetween(2, 10).' days')->format('Y-m-d'),
            'duration_days' => fake()->numberBetween(1, 15),
            'price' => fake()->randomFloat(2, 100, 5000),
            'currency' => 'SYP',
            'status' => Status::ACTIVE->value,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Status::DRAFT->value,
        ]);
    }
}
