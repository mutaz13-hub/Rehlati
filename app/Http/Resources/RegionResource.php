<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $request_type = $request->routeIs('regions.index') ? 'index' : ($request->routeIs('regions.show') ? 'show' : 'else');

        logger($request_type);
        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            //'city_id' => $this->city_id,
            'city' => new CityResource($this->whenLoaded('city')),
            'description' => new DescriptionResource($this->whenLoaded('description')),
            'my_review' => $this->when(auth('sanctum')->check() && $this->relationLoaded('myReview'), fn() => new RatingResource($this->myReview)),
            'pics' => $this->when($request_type === 'show', $this->getMedia('region_pictures')->map(fn($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'name' => $media->name,
                'is_thumbnail' => (bool) $media->getCustomProperty('is_thumbnail'),
            ])),
            'thumbnails' => $this->getMedia('region_pictures')->filter(fn($media) => (bool) $media->getCustomProperty('is_thumbnail'))->map(fn($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'name' => $media->name,
            ])->values(),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}

