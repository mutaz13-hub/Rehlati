<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $request_type = $request->routeIs('regions.index') ? 'index' : ($request->routeIs('regions.show') ? 'show' : 'else');

        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            'status' => $this->status?->value,
            'location' => new LocationResource($this->whenLoaded('location')),
            // 'city_id' => $this->city_id,
            'city' => new CityResource($this->whenLoaded('city')),
            'description' => new DescriptionResource($this->whenLoaded('description')),
            'my_review' => $this->when(auth('sanctum')->check() && $this->relationLoaded('myReview'), fn () => new RatingResource($this->myReview)),
            'pics' => $this->when($request_type === 'show', fn () => PictureResource::collection(
                $this->getMedia('region_pictures')->take(6)->values()
            )),
            'thumbnails' => PictureResource::collection(
                $this->getMedia('region_pictures')->filter(fn ($media) => (bool) $media->getCustomProperty('is_thumbnail'))->values()
            ),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
