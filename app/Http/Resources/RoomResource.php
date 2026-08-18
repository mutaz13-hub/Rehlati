<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray($request): array
    {
        $request_type = $request->routeIs('rooms.index') ? 'index' : ($request->routeIs('rooms.show') ? 'show' : 'else');

        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'name' => app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en,
            'name_en' => $this->when($request->user()?->hasRole('admin'), $this->name_en),
            'name_ar' => $this->when($request->user()?->hasRole('admin'), $this->name_ar),

            'room_class' => $this->room_class?->value,
            'room_layout' => $this->room_layout?->value,

            'max_adults' => $this->max_adults,
            'max_children' => $this->max_children,
            'max_guests' => $this->max_guests,

            'total_beds_count' => $this->whenLoaded('bedTypes', fn () => $this->total_beds_count),
            'total_bed_capacity' => $this->whenLoaded('bedTypes', fn () => $this->total_bed_capacity),

            'pricing' => $this->whenLoaded('prices', function () {
                return new UnifiedPricingResource($this->resource);
            }),

            'hotel' => new HotelResource($this->whenLoaded('hotel')),
            'description' => new DescriptionResource($this->whenLoaded('description')),

            'amenities' => $this->relationLoaded('amenities') ? AmenityResource::collection($this->amenities->take(4)) : null,

            'beds' => $this->whenLoaded('bedTypes', function () {
                return $this->bedTypes->map(fn ($bedType) => array_merge(
                    (new BedTypeResource($bedType))->resolve(),
                    [
                        'capacity' => $bedType->pivot->assigned_capacity,
                    ]
                ))->values();
            }),

            'pics' => $this->when($request_type === 'show', fn () => PictureResource::collection(
                $this->getMedia('room_pictures')->take(6)->values()
            )),
            'thumbnails' => $this->when($request_type === 'else', fn () => PictureResource::collection(
                $this->getMedia('room_pictures')->filter(fn ($media) => (bool) $media->getCustomProperty('is_thumbnail'))->values()
            )),
        ];
    }
}
