<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HotelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $request_type = $request->routeIs('hotels.index') ? 'index' : ($request->routeIs('hotels.show') ? 'show' : 'else');
        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            'stars' => $this->stars,
            'city' => new CityResource($this->whenLoaded('city')),
            'description' => new DescriptionResource($this->whenLoaded('description')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'total_reviews' =>  $this->total_reviews ?? 0,
            'average_rating' =>  $this->average_rating ?? 0,
            'my_review' => $this->when(auth('sanctum')->check() && $this->relationLoaded('myReview'), fn() => new RatingResource($this->myReview)),
            'top_reviews' => $this->when(($request_type === 'show' && $this->relationLoaded('topReviews')) , fn() => RatingResource::collection($this->topReviews->take(3))),
            //'rooms_count' => $this->when(isset($this->rooms_count), $this->rooms_count),
            'rooms' => RoomResource::collection($this->whenLoaded('rooms')),
            'amenities' =>  $this->relationLoaded('amenities') ? AmenityResource::collection($this->amenities->take(4)) : null,
            'pics' => $this->when($request_type === 'show', fn () => PictureResource::collection(
                $this->getMedia('hotel_pictures')->take(10)->values()
            )),
            'thumbnails' => PictureResource::collection(
                $this->getMedia('hotel_pictures')->filter(fn ($media) => (bool) $media->getCustomProperty('is_thumbnail'))->values()
            ),        ];
    }
}
