<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
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
            'user' => $this->when($this->user, fn () => new UserResource($this->user)),
            'rate' => $this->rate,
            'type' => $this->type,
            'body' => $this->body,
            'audio' => $this->when($this->resource->hasMedia('audio_review'), $this->resource->getFirstMediaUrl('audio_review')),
            'photo' => $this->when($this->resource->hasMedia('photo_review'), $this->resource->getFirstMediaUrl('photo_review')),
            'up_votes' => $this->up_votes_count ?? $this->upVotes()->count(),
            'down_votes' => $this->down_votes_count ?? $this->downVotes()->count(),
            'my_vote' => $this->when(auth('sanctum')->check(), function () {
                return $this->votes()->where('user_id', auth('sanctum')->id())?->first()?->vote;
            }),

            'created_at' => $this->created_at?->toDateTimeString(),
            'edited_at' => $this->when($this->edited_at, [
                'datetime' => $this->edited_at?->toDateTimeString(),
                'human_readable' => $this->edited_at?->diffForHumans(),
            ]),
        ];
    }
}
