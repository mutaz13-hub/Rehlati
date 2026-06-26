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
            'description' => new DescriptionResource($this->whenLoaded('description')),
            'total_reviews' => $this->when($request_type !== 'else', $this->total_reviews ?? 0),
            'average_rating' => $this->when($request_type !== 'else', $this->average_rating ?? 0),
            'can_review' => $this->when($request_type !== 'else', $this->can_review),
            'my_review' => $this->when(auth('sanctum')->check() && $this->relationLoaded('myReview'), fn() => new RatingResource($this->myReview)),
            'top_reviews' => $this->when(($request_type === 'show' && $this->relationLoaded('topReviews')) , fn() => RatingResource::collection($this->topReviews->take(3))),
            'pics' => $this->when($request_type === 'show', $this->getMedia('city_pictures')->map(fn($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'name' => $media->name,
                'is_thumbnail' => (bool) $media->getCustomProperty('is_thumbnail'),
            
            ])),
            'thumbnails' => $this->when($request_type !== 'else', $this->getMedia('city_pictures')->filter(fn($media) => (bool) $media->getCustomProperty('is_thumbnail'))->map(fn($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'name' => $media->name,
            ])->values()),
            'top_regions' => $this->when($request_type === 'show' && $this->relationLoaded('topRegions'), fn() => RegionResource::collection($this->topRegions->take(3))),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
