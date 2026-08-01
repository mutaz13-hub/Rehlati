<?php

namespace App\Services;

use App\Models\Region;
use Illuminate\Support\Facades\DB;

class RegionService
{
    public function getAllRegions()
    {
        return Region::with(['description', 'media', 'city'])->get();
    }

    public function createRegion(array $data): void
    {
        DB::transaction(function () use ($data) {
            $region = Region::create([
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'city_id' => $data['city_id'],
            ]);

            if (isset($data['description_en']) || isset($data['description_ar'])) {
                $region->description()->create([
                    'description_en' => $data['description_en'] ?? '',
                    'description_ar' => $data['description_ar'] ?? '',
                ]);
            }

            if (isset($data['pics'])) {
                foreach ($data['pics'] as $pic) {
                    app(ImageUploadService::class)->addUploaded($region, $pic, 'region_pictures');
                }
            }
        });
    }

    public function updateRegion(Region $region, array $data)
    {
        return DB::transaction(function () use ($region, $data) {
            $region->update(array_filter([
                'name_en' => $data['name_en'] ?? null,
                'name_ar' => $data['name_ar'] ?? null,
                'city_id' => $data['city_id'] ?? null,
            ]));

            if (isset($data['description_en']) || isset($data['description_ar'])) {
                $region->description()->updateOrCreate(
                    ['describable_id' => $region->id, 'describable_type' => Region::class],
                    [
                        'description_en' => $data['description_en'] ?? $region->description->description_en ?? '',
                        'description_ar' => $data['description_ar'] ?? $region->description->description_ar ?? '',
                    ]
                );
            }

            return $region;
        });
    }

    public function updateRegionPictures(Region $region, array $data): void
    {
        DB::transaction(function () use ($region, $data) {
            if (isset($data['deleted']) && is_array($data['deleted'])) {
                foreach ($data['deleted'] as $mediaId) {
                    $media = $region->getMedia('region_pictures')->find($mediaId);
                    if ($media) {
                        $media->delete();
                    }
                }
            }

            if (isset($data['added']) && is_array($data['added'])) {
                foreach ($data['added'] as $pic) {
                    app(ImageUploadService::class)->addUploaded($region, $pic, 'region_pictures');
                }
            }
        });
    }

    public function updateRegionThumbnails(Region $region, array $data): void
    {
        DB::transaction(function () use ($region, $data) {
            // Remove thumbnails
            if (isset($data['deleted']) && is_array($data['deleted'])) {
                foreach ($data['deleted'] as $mediaId) {
                    $media = $region->getMedia('region_pictures')->find($mediaId);
                    if ($media) {
                        $media->forgetCustomProperty('is_thumbnail');
                        $media->save();
                    }
                }
            }

            // Add thumbnails (max 3 total)
            if (isset($data['added']) && is_array($data['added'])) {
                // Get current number of thumbnails
                $currentThumbnailCount = $region->getMedia('region_pictures')->filter(fn($media) => (bool) $media->getCustomProperty('is_thumbnail'))->count();
                
                foreach ($data['added'] as $mediaId) {
                    if ($currentThumbnailCount >= 3) break;
                    $media = $region->getMedia('region_pictures')->find($mediaId);
                    if ($media && !$media->getCustomProperty('is_thumbnail')) {
                        $media->setCustomProperty('is_thumbnail', true);
                        $media->save();
                        $currentThumbnailCount++;
                    }
                }
            }
        });
    }

    public function deleteRegion(Region $region)
    {
        return DB::transaction(function () use ($region) {
            $region->description()->delete();
            $region->clearMediaCollection('region_pictures');
            return $region->delete();
        });
    }
}
