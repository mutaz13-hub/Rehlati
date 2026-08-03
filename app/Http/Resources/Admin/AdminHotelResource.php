<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\DescriptionResource;
use App\Http\Resources\LocationResource;
use App\Http\Resources\PictureResource;
use App\Http\Resources\RatingResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminHotelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        logger($request->route()->getName());
        
        $request_type = $request->routeIs('admin.hotels.index') ? 'index' : ($request->routeIs('admin.hotels.show') ? 'show' : 'else');
        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            'stars' => $this->stars,
            'city' => new CityResource($this->whenLoaded('city')),
            'description' => new DescriptionResource($this->whenLoaded('description')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'total_reviews' =>  $this->total_reviews ?? 0,
            'average_rating' =>  $this->average_rating ?? 0,
            'top_reviews' => $this->when(($request_type === 'show' && $this->relationLoaded('topReviews')) , fn() => RatingResource::collection($this->topReviews->take(3))),
            //'rooms_count' => $this->when(isset($this->rooms_count), $this->rooms_count),
            'rooms' => RoomResource::collection($this->whenLoaded('rooms')),
            'amenities' =>  $this->relationLoaded('amenities') ? AmenityResource::collection($this->amenities->take(4)) : null,
            'pics' => $this->when($request_type === 'show', fn () => PictureResource::collection(
                $this->getMedia('hotel_pictures')->take(10)->values()
            )),
            'thumbnails' =>  PictureResource::collection(
                $this->getMedia('hotel_pictures')->filter(fn ($media) => (bool) $media->getCustomProperty('is_thumbnail'))->values()
            ),
        ];
    }
}
