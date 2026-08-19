<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'community_id' => $this->community_id,
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'body' => $this->body,
            'is_self' => $this->user_id === $request->user()?->id,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
