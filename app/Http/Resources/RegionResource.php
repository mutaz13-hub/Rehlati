<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $request_type = $request->routeIs('regions.index') ? 'index' : 'show';
        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            'city_id' => $this->city_id,
            'city' => new CityResource($this->whenLoaded('city')),
            'description' => new DescriptionResource($this->whenLoaded('description')),
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
        ];
    }
}

