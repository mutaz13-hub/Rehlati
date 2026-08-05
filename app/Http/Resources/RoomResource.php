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

            'room_type' => $this->room_type?->value,
            'bed_type' => $this->bed_type?->value,

            'prices' => $this->whenLoaded('prices', function () {
                return $this->prices->map(fn ($price) => [
                    'id' => $price->id,
                    'price_type' => $price->price_type,
                    'nationality_category' => $price->nationality_category,
                    'currency' => $price->currency,
                    'amount' => (float) $price->amount,
                    'season_id' => $price->season_id,
                    'season_name' => $price->season?->name,
                ])->values();
            }),
            'total_rooms' => $this->total_rooms,
            'available_rooms' => $this->available_rooms,

            'hotel' => new HotelResource($this->whenLoaded('hotel')),
            'description' => new DescriptionResource($this->whenLoaded('description')),

            'amenities' => $this->relationLoaded('amenities') ? AmenityResource::collection($this->amenities->take(4)) : null,

            'beds' => $this->whenLoaded('bedTypes', function () {
                return $this->bedTypes->map(fn ($bedType) => [
                    'id' => $bedType->id,
                    'name' => $bedType->localized_name,
                    'slug' => $bedType->slug,
                    'default_capacity' => $bedType->default_capacity,
                    'quantity' => $bedType->pivot->quantity,
                    'assigned_capacity' => $bedType->pivot->assigned_capacity,
                ])->values();
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
