<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'visibility' => $this->visibility->value,
            'cover_image' => $this->hasMedia('community_covers') ? $this->getFirstMediaUrl('community_covers') : null,
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ]),
            'members_count' => $this->whenCounted('members_count'),
            'posts_count' => $this->whenCounted('posts'),
            'my_role' => $this->roleFor(auth('sanctum')->user()),
            'my_membership_status' => $this->membershipFor(auth('sanctum')->user())?->status?->value,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
