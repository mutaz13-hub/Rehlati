<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\CarAgencyResource;
use App\Http\Resources\DescriptionResource;
use App\Http\Resources\PictureResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $request_type = $request->routeIs('admin.packages.index') ? 'index' : ($request->routeIs('admin.packages.show') ? 'show' : 'else');

        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            'name_en' => $this->when($request_type === 'show', $this->name_en),
            'name_ar' => $this->when($request_type === 'show', $this->name_ar),
            'description' => new DescriptionResource($this->whenLoaded('description')),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'duration_days' => $this->duration_days,
            'price' => $this->price !== null ? (float) $this->price : null,
            'currency' => $this->currency,
            'status' => $this->status?->value,
            'prices' => $this->whenLoaded('prices', function () {
                return AdminPriceResource::collection($this->prices);
            }),
            'pics' => $this->when($request_type === 'show', fn () => PictureResource::collection(
                $this->getMedia('package_pictures')->take(10)->values()
            )),
            'thumbnails' => $this->when($request_type !== 'else', fn () => PictureResource::collection(
                $this->getMedia('package_pictures')->filter(fn ($media) => (bool) $media->getCustomProperty('is_thumbnail'))->values()
            )),
            'regions' => $this->when($request_type === 'show', fn () => AdminRegionResource::collection($this->whenLoaded('regions'))),
            'cities' => $this->when($request_type === 'show', fn () => AdminCityResource::collection($this->whenLoaded('cities'))),
            'hotels' => $this->when($request_type === 'show', fn () => AdminHotelResource::collection($this->whenLoaded('hotels'))),
            'car_agencies' => $this->when($request_type === 'show', []), // fn () => CarAgencyResource::collection($this->whenLoaded('carAgencies'))),
            'tourist_guides' => $this->when($request_type === 'show', fn () => AdminTouristGuideResource::collection($this->whenLoaded('touristGuides'))),
        ];
    }
}
