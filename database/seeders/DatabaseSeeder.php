<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SocialProviderSeeder::class,
            TagSeeder::class,
            SyrianCitiesSeeder::class,
            LocationSeeder::class,
            AmenitySeeder::class,
            HotelSeeder::class,
            RoomSeeder::class,
            RoomAmenitySeeder::class,
            CityAndRegionMediaSeeder::class,
            PackageSeeder::class,
            UserSeeder::class,
            TripSeeder::class,
            RatingSeeder::class,
            AdminSeeder::class,
            BedTypeSeeder::class,
        ]);
    }
}
