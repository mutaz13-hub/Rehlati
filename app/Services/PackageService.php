<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Package;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PackageService
{
    private const LIST_RELATIONS = ['description', 'regions', 'cities', 'hotels', 'carAgencies'];

    public function index(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = Package::with(self::LIST_RELATIONS)->where('end_date', '>=', now()->toDateString());

        //$this->applyFilters($query, $filters);

        return $query->orderBy('start_date', 'desc')->paginate($perPage);
    }

    public function createPackage(array $data): void
    {
         DB::transaction(function () use ($data) {
            $package = Package::create([
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'duration_days' => $data['duration_days'] ?? null,
                'price' => $data['price'] ?? null,
                'currency' => $data['currency'] ?? null,
                'status' => $data['status'] ?? Status::DRAFT->value,
            ]);

            if (isset($data['description_en']) || isset($data['description_ar'])) {
                $package->description()->create([
                    'description_en' => $data['description_en'] ?? '',
                    'description_ar' => $data['description_ar'] ?? '',
                ]);
            }

            $package->regions()->sync($data['regions'] ?? []);
            $package->cities()->sync($data['cities'] ?? []);
            $package->hotels()->sync($data['hotels'] ?? []);
            $package->carAgencies()->sync($data['car_agencies'] ?? []);
        });
    }

    public function updatePackage(Package $package, array $data): void
    {
         DB::transaction(function () use ($package, $data) {
            $updates = [];
            foreach (['name_en', 'name_ar', 'start_date', 'end_date', 'duration_days', 'price', 'currency', 'status'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            if (! empty($updates)) {
                $package->update($updates);
            }

            if (isset($data['description_en']) || isset($data['description_ar'])) {
                $package->description()->updateOrCreate(
                    ['describable_id' => $package->id, 'describable_type' => Package::MORPH_KEY],
                    [
                        'description_en' => $data['description_en'] ?? $package->description?->description_en ?? '',
                        'description_ar' => $data['description_ar'] ?? $package->description?->description_ar ?? '',
                    ]
                );
            }

            foreach (['regions' => 'regions', 'cities' => 'cities', 'hotels' => 'hotels', 'car_agencies' => 'carAgencies'] as $key => $relation) {
                if (array_key_exists($key, $data)) {
                    $package->{$relation}()->sync($data[$key] ?? []);
                }
            }

            
        });
    }

    public function updatePackagePictures(Package $package, array $data): void
    {
        DB::transaction(function () use ($package, $data) {
            if (isset($data['deleted']) && is_array($data['deleted'])) {
                foreach ($data['deleted'] as $mediaId) {
                    $media = $package->getMedia('package_pictures')->find($mediaId);
                    if ($media) {
                        $media->delete();
                    }
                }
            }

            if (isset($data['added']) && is_array($data['added'])) {
                foreach ($data['added'] as $pic) {
                    app(ImageUploadService::class)->addUploaded($package, $pic, 'package_pictures');
                }
            }
        });
    }

    public function updatePackageThumbnails(Package $package, array $data): void
    {
        DB::transaction(function () use ($package, $data) {
            if (isset($data['deleted']) && is_array($data['deleted'])) {
                foreach ($data['deleted'] as $mediaId) {
                    $media = $package->getMedia('package_pictures')->find($mediaId);
                    if ($media) {
                        $media->forgetCustomProperty('is_thumbnail');
                        $media->save();
                    }
                }
            }

            if (isset($data['added']) && is_array($data['added'])) {
                $currentThumbnailCount = $package->getMedia('package_pictures')->filter(fn ($media) => (bool) $media->getCustomProperty('is_thumbnail'))->count();

                foreach ($data['added'] as $mediaId) {
                    if ($currentThumbnailCount >= 3) {
                        break;
                    }

                    $media = $package->getMedia('package_pictures')->find($mediaId);
                    if ($media && ! $media->getCustomProperty('is_thumbnail')) {
                        $media->setCustomProperty('is_thumbnail', true);
                        $media->save();
                        $currentThumbnailCount++;
                    }
                }
            }
        });
    }

    public function deletePackage(Package $package): void
    {
        DB::transaction(function () use ($package) {
            $package->description()->delete();
            $package->delete();
        });
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['q'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('name_en', 'like', "%{$filters['q']}%")
                    ->orWhere('name_ar', 'like', "%{$filters['q']}%")
                    ->orWhereHas('description', function (Builder $dq) use ($filters) {
                        $dq->where('description_en', 'like', "%{$filters['q']}%")
                            ->orWhere('description_ar', 'like', "%{$filters['q']}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        foreach (['start_date' => ['start_date_from', 'start_date_to'], 'end_date' => ['end_date_from', 'end_date_to']] as $column => [$fromKey, $toKey]) {
            if (! empty($filters[$fromKey])) {
                $query->whereDate($column, '>=', $filters[$fromKey]);
            }
            if (! empty($filters[$toKey])) {
                $query->whereDate($column, '<=', $filters[$toKey]);
            }
        }

        foreach (['region_id' => 'regions', 'city_id' => 'cities', 'hotel_id' => 'hotels', 'car_agency_id' => 'carAgencies'] as $filterKey => $relation) {
            if (! empty($filters[$filterKey])) {
                $query->whereHas($relation, fn (Builder $q) => $q->whereKey($filters[$filterKey]));
            }
        }
    }
}
