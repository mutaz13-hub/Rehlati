<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\DescriptionResource;
use App\Http\Resources\PictureResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRoomResource extends JsonResource
{
    public function toArray($request): array
    {
        $request_type = $request->routeIs('admin.rooms.index') ? 'index' : ($request->routeIs('admin.rooms.show') ? 'show' : 'else');

        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'name' => $this->when($request_type === 'index', $this->localized_name),
            'name_en' => $this->when($request_type === 'show', $this->name_en),
            'name_ar' => $this->when($request_type === 'show', $this->name_ar),

            'room_class' => $this->room_class?->value,
            'room_layout' => $this->room_layout?->value,

            'max_adults' => $this->max_adults,
            'max_children' => $this->max_children,
            'max_guests' => $this->max_guests,

            'prices' => $this->whenLoaded('prices', function () {
                return AdminPriceResource::collection($this->prices->take(5));
            }),
            'total_rooms' => $this->total_rooms,
            'available_rooms' => $this->available_rooms,

            'hotel' => new HotelResource($this->whenLoaded('hotel')),
            'description' => new DescriptionResource($this->whenLoaded('description')),

            'amenities' => $this->relationLoaded('amenities') ? AmenityResource::collection($this->amenities->take(4)) : null,

            'beds' => $this->whenLoaded('bedTypes', function () {
                return $this->bedTypes->map(fn ($bedType) => array_merge(
                    (new AdminBedTypeResource($bedType))->resolve(),
                    [
                        'quantity' => $bedType->pivot->quantity,
                        'assigned_capacity' => $bedType->pivot->assigned_capacity,
                    ]
                ))->values();
            }),

            'pics' => $this->when($request_type === 'show', fn () => PictureResource::collection(
                $this->getMedia('room_pictures')->take(6)->values()
            )),
            'thumbnails' => $this->when($request_type !== 'show', fn () => PictureResource::collection(
                $this->getMedia('room_pictures')->filter(fn ($media) => (bool) $media->getCustomProperty('is_thumbnail'))->values()
            )),
        ];
    }
}
