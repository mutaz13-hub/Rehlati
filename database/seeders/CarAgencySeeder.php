<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\CarAgency;
use Illuminate\Database\Seeder;

class CarAgencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $existingCount = CarAgency::query()->count();
        $desiredCount = 3;

        for ($i = $existingCount; $i < $desiredCount; $i++) {
            CarAgency::create([
                'status' => Status::ACTIVE->value,
            ]);
        }
    }
}
