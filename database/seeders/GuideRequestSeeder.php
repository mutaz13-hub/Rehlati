<?php

namespace Database\Seeders;

use App\Enums\GuideRequestStatus;
use App\Models\GuideRequest;
use App\Models\TouristGuide;
use App\Models\Trip;
use Illuminate\Database\Seeder;

class GuideRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trip = Trip::where('title', 'Damascus Weekend')->first();
        $guide = TouristGuide::where('email', 'lina@example.com')->first();

        if (! $trip || ! $guide) {
            $this->command->info('No trips or tourist guides found to seed guide requests.');

            return;
        }

        GuideRequest::updateOrCreate(
            ['trip_id' => $trip->id, 'tourist_guide_id' => $guide->id],
            [
                'status' => GuideRequestStatus::PENDING->value,
                'note' => 'We would love a guide who speaks English for the weekend.',
            ],
        );
    }
}
