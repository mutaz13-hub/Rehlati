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
            // 'name_en' => $this->name_en,
            // 'name_ar' => $this->name_ar,
            'name' => app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en,
            'hotel' => new HotelResource($this->whenLoaded('hotel')),
            'room_type' => $this->room_type?->value,
            'bed_type' => $this->bed_type?->value,
            'description' => new DescriptionResource($this->whenLoaded('description')),
            'amenities' => $this->relationLoaded('amenities') ? AmenityResource::collection($this->amenities->take(4)) : null,
            'pics' => $this->when($request_type === 'show', fn () => PictureResource::collection(
                $this->getMedia('room_pictures')->take(6)->values()
            )),
            'thumbnails' => $this->when($request_type === 'else', fn () => PictureResource::collection(
                $this->getMedia('room_pictures')->filter(fn ($media) => (bool) $media->getCustomProperty('is_thumbnail'))->values()
            )),
        ];
    }
}
