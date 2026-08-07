<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\DescriptionResource;
use App\Http\Resources\LocationResource;
use App\Http\Resources\PictureResource;
use App\Http\Resources\RatingResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCityResource extends JsonResource
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
            'status' => $this->status?->value,
            'location' => new LocationResource($this->whenLoaded('location')),
            'description' => new DescriptionResource($this->whenLoaded('description')),
            'total_reviews' => $this->when($request_type !== 'else', $this->total_reviews ?? 0),
            'average_rating' => $this->when($request_type !== 'else', $this->average_rating ?? 0),
            'top_reviews' => $this->when(($request_type === 'show' && $this->relationLoaded('topReviews')), fn () => RatingResource::collection($this->topReviews->take(3))),
            'pics' => $this->when($request_type === 'show', fn () => PictureResource::collection(
                $this->getMedia('city_pictures')->take(6)->values()
            )),
            'thumbnails' => PictureResource::collection(
                $this->getMedia('city_pictures')->filter(fn ($media) => (bool) $media->getCustomProperty('is_thumbnail'))->values()
            ),
            'top_regions' => $this->when($request_type === 'show' && $this->relationLoaded('topRegions'), fn () => RegionResource::collection($this->topRegions->take(3))),
            'top_hotels' => $this->when($request_type === 'show' && $this->relationLoaded('top_hotels'), fn () => HotelResource::collection($this->top_hotels->take(3))),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
