<?php

namespace App\Services\Admin;

use App\Models\Hotel;
use App\Services\ImageUploadService;
use Illuminate\Support\Facades\DB;

class AdminHotelService
{
    public function create(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $hotel = Hotel::create([
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'city_id' => $data['city_id'],
                'stars' => $data['stars'],
            ]);

            if (isset($data['description_en']) || isset($data['description_ar'])) {
                $hotel->description()->create([
                    'description_en' => $data['description_en'] ?? '',
                    'description_ar' => $data['description_ar'] ?? '',
                ]);
            }

            if (isset($data['longitude']) && isset($data['latitude'])) {
                $hotel->location()->create([
                    'longitude' => $data['longitude'],
                    'latitude' => $data['latitude'],
                ]);
            }

            if (isset($data['phone']) || isset($data['email'])) {
                $hotel->contactDetails()->create([
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                ]);
            }

            foreach ($data['pics'] ?? [] as $picture) {
                app(ImageUploadService::class)->addUploaded($hotel, $picture, 'hotel_pictures');
            }

            if (isset($data['amenities']) && is_array($data['amenities'])) {
                $hotel->amenities()->sync($data['amenities']);
            }
        });
    }

    public function updateHotelPictures(Hotel $hotel, array $data): void
    {
        DB::transaction(function () use ($hotel, $data) {
            if (isset($data['deleted']) && is_array($data['deleted'])) {
                foreach ($data['deleted'] as $mediaId) {
                    $media = $hotel->getMedia('hotel_pictures')->find($mediaId);
                    if ($media) {
                        $media->delete();
                    }
                }
            }

            if (isset($data['added']) && is_array($data['added'])) {
                foreach ($data['added'] as $pic) {
                    app(ImageUploadService::class)->addUploaded($hotel, $pic, 'hotel_pictures');
                }
            }
        });
    }

    public function updateHotelThumbnails(Hotel $hotel, array $data): void
    {
        DB::transaction(function () use ($hotel, $data) {
            // Remove thumbnails
            if (isset($data['deleted']) && is_array($data['deleted'])) {
                foreach ($data['deleted'] as $mediaId) {
                    $media = $hotel->getMedia('hotel_pictures')->find($mediaId);
                    if ($media) {
                        $media->forgetCustomProperty('is_thumbnail');
                        $media->save();
                    }
                }
            }

            // Add thumbnails (max 3 total)
            if (isset($data['added']) && is_array($data['added'])) {
                // Get current number of thumbnails
                $currentThumbnailCount = $hotel->getMedia('hotel_pictures')->filter(fn ($media) => (bool) $media->getCustomProperty('is_thumbnail'))->count();

                foreach ($data['added'] as $mediaId) {
                    if ($currentThumbnailCount >= 3) {
                        break;
                    }
                    $media = $hotel->getMedia('hotel_pictures')->find($mediaId);
                    if ($media && ! $media->getCustomProperty('is_thumbnail')) {
                        $media->setCustomProperty('is_thumbnail', true);
                        $media->save();
                        $currentThumbnailCount++;
                    }
                }
            }
        });
    }

    public function update(Hotel $hotel, array $data): void
    {
        DB::transaction(function () use ($hotel, $data): void {
            $hotel->update(array_filter([
                'name_en' => $data['name_en'] ?? null,
                'name_ar' => $data['name_ar'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'stars' => $data['stars'] ?? null,
            ]));

            if (isset($data['description_en']) || isset($data['description_ar'])) {
                $hotel->description()->updateOrCreate(
                    ['describable_id' => $hotel->id, 'describable_type' => Hotel::MORPH_KEY],
                    [
                        'description_en' => $data['description_en'] ?? $hotel->description->description_en ?? null,
                        'description_ar' => $data['description_ar'] ?? $hotel->description->description_ar ?? null,
                    ]
                );
            }

            if (isset($data['longitude']) && isset($data['latitude'])) {
                $hotel->location()->updateOrCreate(
                    ['locatable_id' => $hotel->id, 'locatable_type' => Hotel::MORPH_KEY],
                    [
                        'longitude' => $data['longitude'],
                        'latitude' => $data['latitude'],
                    ]
                );
            }

            if (isset($data['phone']) || isset($data['email'])) {
                $hotel->contactDetails()->updateOrCreate(
                    ['contactable_id' => $hotel->id, 'contactable_type' => Hotel::MORPH_KEY],
                    [
                        'phone' => $data['phone'] ?? $hotel->contactDetails?->phone,
                        'email' => $data['email'] ?? $hotel->contactDetails?->email,
                    ]
                );
            }

            if (isset($data['amenities']) && is_array($data['amenities'])) {
                $hotel->amenities()->sync($data['amenities']);
            }
        });
    }

    public function delete(Hotel $hotel): void
    {
        DB::transaction(function () use ($hotel): void {
            $hotel->delete();
        });
    }
}
