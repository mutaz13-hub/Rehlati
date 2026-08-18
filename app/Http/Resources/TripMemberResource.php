<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
        ];
    }
}
