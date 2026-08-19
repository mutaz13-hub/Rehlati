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
            ExchangeRateSeeder::class,
            AppSettingSeeder::class,
            RoomSeeder::class,
            RoomAmenitySeeder::class,
            CityAndRegionMediaSeeder::class,
            TouristGuideSeeder::class,
            PackageSeeder::class,
            UserSeeder::class,
            TripSeeder::class,
            GuideRequestSeeder::class,
            RatingSeeder::class,
            AdminSeeder::class,
            BedTypeSeeder::class,
            BookingSeeder::class,
            AdminBookingSeeder::class,
            CommunitySeeder::class,
        ]);
    }
}
