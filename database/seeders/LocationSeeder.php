<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        City::query()->eachById(function (City $city): void {
            $city->location()->updateOrCreate([], [
                'latitude' => fake()->latitude(32.0, 37.3),
                'longitude' => fake()->longitude(35.7, 42.4),
            ]);
        });

        Region::query()->eachById(function (Region $region): void {
            $region->location()->updateOrCreate([], [
                'latitude' => fake()->latitude(32.0, 37.3),
                'longitude' => fake()->longitude(35.7, 42.4),
            ]);
        });
    }
}
