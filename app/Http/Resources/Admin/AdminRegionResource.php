<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\DescriptionResource;
use App\Http\Resources\LocationResource;
use App\Http\Resources\PictureResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $request_type = $request->routeIs('admin.regions.index') ? 'index' : ($request->routeIs('admin.regions.show') ? 'show' : 'else');

        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            'status' => $this->status?->value,
            'location' => new LocationResource($this->whenLoaded('location')),
            // 'city_id' => $this->city_id,
            'city' => new CityResource($this->whenLoaded('city')),
            'description' => new DescriptionResource($this->whenLoaded('description')),
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
