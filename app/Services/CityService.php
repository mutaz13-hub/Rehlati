<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class CityService
{
    public function getAllCities()
    {
        return City::with(['description', 'media'])->get();
    }

    public function createCity(array $data): void
    {
         DB::transaction(function () use ($data) {
            $city = City::create([
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
            ]);

            if (isset($data['description_en']) || isset($data['description_ar'])) {
                $city->description()->create([
                    'description_en' => $data['description_en'] ?? '',
                    'description_ar' => $data['description_ar'] ?? '',
                ]);
            }

            if (isset($data['pics'])) {
                foreach ($data['pics'] as $pic) {
                    app(ImageUploadService::class)->addUploaded($city, $pic, 'city_pictures');
                }
            }

            if(isset($data['longitude']) && isset($data['latitude'])) {
                $city->location()->create([
                    'longitude' => $data['longitude'],
                    'latitude' => $data['latitude'],
                ]);
            }

            if (isset($data['tags']) && is_array($data['tags'])) {
                $city->tags()->sync($data['tags']);
            }
        });
    }

    public function updateCity(City $city, array $data): void
    {
         DB::transaction(function () use ($city, $data) {
            $city->update(array_filter([
                'name_en' => $data['name_en'] ?? null,
                'name_ar' => $data['name_ar'] ?? null,
            ]));

            if (isset($data['description_en']) || isset($data['description_ar'])) {
                $city->description()->updateOrCreate(
                    ['describable_id' => $city->id, 'describable_type' => City::MORPH_KEY],
                    [
                        'description_en' => $data['description_en'] ?? $city->description->description_en ?? null,
                        'description_ar' => $data['description_ar'] ?? $city->description->description_ar ?? null,
                    ]
                );
            }
            if (isset($data['longitude']) && isset($data['latitude'])) {
                $city->location()->updateOrCreate(
                    ['locatable_id' => $city->id, 'locatable_type' => City::MORPH_KEY],
                    [
                        'longitude' => $data['longitude'],
                        'latitude' => $data['latitude'],
                    ]
                );
            }

            if (isset($data['tags']) && is_array($data['tags'])) {
                $city->tags()->sync($data['tags']);
            }

        });
    }

    public function updateCityPictures(City $city, array $data): void
    {
        DB::transaction(function () use ($city, $data) {
            if (isset($data['deleted']) && is_array($data['deleted'])) {
                foreach ($data['deleted'] as $mediaId) {
                    $media = $city->getMedia('city_pictures')->find($mediaId);
                    if ($media) {
                        $media->delete();
                    }
                }
            }

            if (isset($data['added']) && is_array($data['added'])) {
                foreach ($data['added'] as $pic) {
                    app(ImageUploadService::class)->addUploaded($city, $pic, 'city_pictures');
                }
            }
        });
    }

    public function updateCityThumbnails(City $city, array $data): void
    {
        DB::transaction(function () use ($city, $data) {
            // Remove thumbnails
            if (isset($data['deleted']) && is_array($data['deleted'])) {
                foreach ($data['deleted'] as $mediaId) {
                    $media = $city->getMedia('city_pictures')->find($mediaId);
                    if ($media) {
                        $media->forgetCustomProperty('is_thumbnail');
                        $media->save();
                    }
                }
            }

            // Add thumbnails (max 3 total)
            if (isset($data['added']) && is_array($data['added'])) {
                // Get current number of thumbnails
                $currentThumbnailCount = $city->getMedia('city_pictures')->filter(fn($media) => (bool) $media->getCustomProperty('is_thumbnail'))->count();
                
                foreach ($data['added'] as $mediaId) {
                    if ($currentThumbnailCount >= 3) break;
                    $media = $city->getMedia('city_pictures')->find($mediaId);
                    if ($media && !$media->getCustomProperty('is_thumbnail')) {
                        $media->setCustomProperty('is_thumbnail', true);
                        $media->save();
                        $currentThumbnailCount++;
                    }
                }
            }
        });
    }

    public function deleteCity(City $city)
    {
        return DB::transaction(function () use ($city) {
            $city->description()->delete();
            $city->clearMediaCollection('city_pictures');
            return $city->delete();
        });
    }
}
