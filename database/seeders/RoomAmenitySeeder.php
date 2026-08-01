<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Amenity;
use Illuminate\Database\Seeder;

class RoomAmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenityIds = Amenity::query()->pluck('id');

        Room::query()->eachById(function (Room $room) use ($amenityIds): void {
            $room->amenities()->sync(
                $amenityIds->random(min(fake()->numberBetween(3, 6), $amenityIds->count()))->all()
            );
        });
    }
}
