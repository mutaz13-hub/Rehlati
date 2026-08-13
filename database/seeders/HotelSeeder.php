<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\City;
use App\Models\Amenity;
use App\Models\AmenityHotel;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $damascus = City::where('name_en', '=', 'Damascus')->first();
        $aleppo = City::where('name_en', '=', 'Aleppo')->first();
        $homs = City::where('name_en', '=', 'Homs')->first();
        $latakia = City::where('name_en', '=', 'Latakia')->first();

        if (!$damascus || !$aleppo || !$homs || !$latakia) {
            $this->command->error('Required cities not found. Please run SyrianCitiesSeeder first.');
            return;
        }

        $hotels = [
            [
                'name_en' => 'Al-Sham Palace Hotel',
                'name_ar' => 'فندق قصر الشام',
                'city_id' => $damascus->id,
                'stars' => 5.00,
                'latitude' => 33.5138 + (mt_rand(-50, 50) / 10000),
                'longitude' => 36.2765 + (mt_rand(-50, 50) / 10000),
            ],
            [
                'name_en' => 'Aleppo Grand Hotel',
                'name_ar' => 'فندق حلب الكبير',
                'city_id' => $aleppo->id,
                'stars' => 4.50,
                'latitude' => 36.2048 + (mt_rand(-50, 50) / 10000),
                'longitude' => 37.1381 + (mt_rand(-50, 50) / 10000),
            ],
            [
                'name_en' => 'Homs View Hotel',
                'name_ar' => 'فندق إطلالة حمص',
                'city_id' => $homs->id,
                'stars' => 4.00,
                'latitude' => 34.7324 + (mt_rand(-50, 50) / 10000),
                'longitude' => 36.7137 + (mt_rand(-50, 50) / 10000),
            ],
            [
                'name_en' => 'Latakia Beach Resort',
                'name_ar' => 'منتجع شاطئ اللاذقية',
                'city_id' => $latakia->id,
                'stars' => 4.50,
                'latitude' => 35.5281 + (mt_rand(-50, 50) / 10000),
                'longitude' => 35.7857 + (mt_rand(-50, 50) / 10000),
            ],
        ];

        $amenities = Amenity::all();

        foreach ($hotels as $hotelData) {
            $hotel = Hotel::updateOrCreate(
                ['name_en' => $hotelData['name_en']],
                [
                    'name_en' => $hotelData['name_en'],
                    'name_ar' => $hotelData['name_ar'],
                    'city_id' => $hotelData['city_id'],
                    'stars' => $hotelData['stars'],
                ]
            );

            $hotel->location()->updateOrCreate(
                [],
                [
                    'latitude' => $hotelData['latitude'],
                    'longitude' => $hotelData['longitude'],
                ]
            );

            $randomAmenities = $amenities->random(mt_rand(8, 15));
            foreach ($randomAmenities as $amenity) {
                AmenityHotel::updateOrCreate(
                    ['hotel_id' => $hotel->id, 'amenity_id' => $amenity->id],
                    ['hotel_id' => $hotel->id, 'amenity_id' => $amenity->id]
                );
            }

            $this->seedFakeMedia($hotel, 'hotel_pictures', 4);
        }
    }

    protected function seedFakeMedia(\Spatie\MediaLibrary\HasMedia $model, string $collectionName, int $count): void
    {
        $model->clearMediaCollection($collectionName);

        for ($i = 0; $i < $count; $i++) {
            try {
                $imageUrl = "http://picsum.photos/800/600?random={$i}" . uniqid();
                
                $media = app(\App\Services\ImageUploadService::class)
                    ->addFromUrl($model, $imageUrl, $collectionName);
                
                if ($i < 3) {
                    $media->setCustomProperty('is_thumbnail', true);
                    $media->save();
                }
            } catch (\Exception) {
                continue;
            }
        }
    }
}
