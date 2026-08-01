<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AmenityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            'slug' => $this->slug
        ];
    }
}
