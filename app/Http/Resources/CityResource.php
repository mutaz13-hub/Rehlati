<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $request_type = $request->routeIs('cities.index') ? 'index' : ($request->routeIs('cities.show') ? 'show' : 'else');
        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            'location' => new LocationResource($this->whenLoaded('location')),
            'description' => new DescriptionResource($this->whenLoaded('description')),
            'total_reviews' => $this->when($request_type !== 'else', $this->total_reviews ?? 0),
            'average_rating' => $this->when($request_type !== 'else', $this->average_rating ?? 0),
            'can_review' => $this->when($request_type !== 'else', $this->can_review),
            'my_review' => $this->when(auth('sanctum')->check() && $this->relationLoaded('myReview'), fn() => new RatingResource($this->myReview)),
            'top_reviews' => $this->when(($request_type === 'show' && $this->relationLoaded('topReviews')) , fn() => RatingResource::collection($this->topReviews->take(3))),
            'pics' => $this->when($request_type === 'show', fn () => PictureResource::collection(
                $this->getMedia('city_pictures')->take(6)->values()
            )),
            'thumbnails' => $this->when($request_type !== 'else', fn () => PictureResource::collection(
                $this->getMedia('city_pictures')->filter(fn ($media) => (bool) $media->getCustomProperty('is_thumbnail'))->values()
            )),
            'top_regions' => $this->when($request_type === 'show' && $this->relationLoaded('topRegions'), fn() => RegionResource::collection($this->topRegions->take(3))),
            'top_hotels' => $this->when($request_type === 'show' && $this->relationLoaded('top_hotels'), fn() => HotelResource::collection($this->top_hotels->take(3))),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
