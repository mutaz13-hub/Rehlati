<?php

namespace App\Http\Resources;

use App\Enums\VoteType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'post_id' => $this->post_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'type' => $this->type->value,
            'body' => $this->body,
            'pictures' => PictureResource::collection($this->getMedia('comment_pictures')),
            'video' => $this->hasMedia('comment_videos') ? $this->getFirstMediaUrl('comment_videos') : null,
            'audio' => $this->hasMedia('comment_audio') ? $this->getFirstMediaUrl('comment_audio') : null,
            'up_votes' => $this->up_votes_count ?? $this->voteTotals->firstWhere('vote_type', VoteType::UP)?->count ?? 0,
            'down_votes' => $this->down_votes_count ?? $this->voteTotals->firstWhere('vote_type', VoteType::DOWN)?->count ?? 0,
            'my_vote' => $this->when(auth('sanctum')->check(), function () {
                return $this->votes()->where('user_id', auth('sanctum')->id())?->first()?->vote;
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
