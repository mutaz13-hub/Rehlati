<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $request_type = $request->routeIs('packages.index') ? 'index' : ($request->routeIs('packages.show') ? 'show' : 'else');

        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            'name_en' => $this->when($request->user()?->hasRole('admin'), $this->name_en),
            'name_ar' => $this->when($request->user()?->hasRole('admin'), $this->name_ar),
            'description' => new DescriptionResource($this->whenLoaded('description')),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'duration_days' => $this->duration_days,
            'price' => $this->price !== null ? (float) $this->price : null,
            'currency' => $this->currency,
            'status' => $this->status?->value,
            'pics' => $this->when($request_type === 'show', fn () => PictureResource::collection(
                $this->getMedia('package_pictures')->take(10)->values()
            )),
            'thumbnails' => $this->when($request_type !== 'else', fn () => PictureResource::collection(
                $this->getMedia('package_pictures')->filter(fn ($media) => (bool) $media->getCustomProperty('is_thumbnail'))->values()
            )),
            'regions' => $this->associatedIds('regions'),
            'cities' => $this->associatedIds('cities'),
            'hotels' => $this->associatedIds('hotels'),
            'car_agencies' => $this->associatedIds('carAgencies'),
        ];
    }

    private function associatedIds(string $relation): array
    {
        return $this->relationLoaded($relation) ? $this->{$relation}->pluck('id')->all() : [];
    }
}
