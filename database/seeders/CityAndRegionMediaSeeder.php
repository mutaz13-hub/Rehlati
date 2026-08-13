<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Seeder;

class CityAndRegionMediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = City::with('regions')->limit(3)->get();

        foreach ($cities as $city) {
            // Seed 5 fake images for each city, with 3 marked as thumbnails
            $this->seedFakeMedia($city, 'city_pictures', 5);

            // Seed 5 fake images for each region of the city
            $count = 0;
            foreach ($city->regions as $region) {
                if($count == 2) break;
                $this->seedFakeMedia($region, 'region_pictures', 5);
                $count++;
            }
        }
    }

    /**
     * Seed fake media for a model using Picsum, with first 3 marked as thumbnails.
     *
     * @param \Spatie\MediaLibrary\HasMedia $model
     * @param string $collectionName
     * @param int $count
     * @return void
     */
    protected function seedFakeMedia(\Spatie\MediaLibrary\HasMedia $model, string $collectionName, int $count): void
    {
        // Clear existing media to prevent duplicates
        $model->clearMediaCollection($collectionName);

        for ($i = 0; $i < $count; $i++) {
            try {
                // Use Picsum for fake images
                $imageUrl = "http://picsum.photos/800/600?random={$i}" . uniqid();
                
                $media = app(\App\Services\ImageUploadService::class)
                    ->addFromUrl($model, $imageUrl, $collectionName);
                
                // Mark first 3 images as thumbnails
                if ($i < 3) {
                    $media->setCustomProperty('is_thumbnail', true);
                    $media->save();
                }
            } catch (\Exception $e) {
                // If downloading fails, skip this image
                continue;
            }
        }
    }
}
