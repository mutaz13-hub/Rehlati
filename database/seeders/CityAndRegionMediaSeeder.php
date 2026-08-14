<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CityAndRegionMediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = City::with('regions')->limit(3)->get();

        foreach ($cities as $city) {
            // Seed 5 fake images for each city
            $this->seedFakeMedia($city, 'city_pictures', 5);

            // Seed 5 fake images for each region of the city
            $count = 0;
            foreach ($city->regions as $region) {
                if ($count == 2) break;
                $this->seedFakeMedia($region, 'region_pictures', 5);
                $count++;
            }
        }
    }

    /**
     * Seed fake media locally for a model.
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

        // Ensure a temporary directory exists
        $tempDir = storage_path('app/temp_images');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        for ($i = 0; $i < $count; $i++) {
            try {
                // 1. Create a quick, unique dummy image file locally (No HTTP needed)
                $tempPath = $tempDir . '/fake_' . uniqid() . '.jpg';
                $this->createLocalDummyImage($tempPath);

                // 2. Attach the local file to Spatie Media Library
                // Replace the custom service call with Spatie's native local file handler
                $media = $model->addMedia($tempPath)
                    ->toMediaCollection($collectionName);
                
                // 3. Mark first 3 images as thumbnails
                if ($i < 3) {
                    $media->setCustomProperty('is_thumbnail', true);
                    $media->save();
                }
            } catch (\Exception $e) {
                // Log errors if something goes wrong, but keep seeding
                logger()->error("Failed seeding media: " . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Helper to generate a genuine blank JPEG image file locally.
     */
    protected function createLocalDummyImage(string $path): void
    {
        $image = imagecreatetruecolor(800, 600);
        
        // Fill background with a random color so they look distinct
        $bgColor = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
        imagefill($image, 0, 0, $bgColor);
        
        // Save file and free memory
        imagejpeg($image, $path);
        imagedestroy($image);
    }
}
