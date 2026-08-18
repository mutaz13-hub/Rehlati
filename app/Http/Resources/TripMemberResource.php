<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'username' => $this->whenLoaded('user', fn () => $this->user->username),
            'avatar' => $this->whenLoaded('user', fn () => $this->user->avatar),
            'role' => $this->role->value,
            'status' => $this->status?->value,
        ];
    }
}
