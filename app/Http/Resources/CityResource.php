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
        $request_type = $request->routeIs('cities.index') ? 'index' : 'show';
        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            'description' => new DescriptionResource($this->whenLoaded('description')),
            'total_reviews' => $this->total_reviews ?? 0,
            'average_rating' => $this->average_rating ?? 0,
            'can_review' => $this->can_review,
            'top_reviews' => RatingResource::collection($this->whenLoaded('topReviews')),
            'pics' => $this->when($request_type === 'show', $this->getMedia('city_pictures')->map(fn($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'name' => $media->name,
                'is_thumbnail' => (bool) $media->getCustomProperty('is_thumbnail'),
            
            ])),
            'thumbnails' => $this->getMedia('city_pictures')->filter(fn($media) => (bool) $media->getCustomProperty('is_thumbnail'))->map(fn($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'name' => $media->name,
            ])->values(),
        ];
    }
}
