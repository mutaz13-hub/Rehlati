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
            'user' => $this->when($this->user, [
                'id' => $this->user->id,
                'name' => $this->user->name ?? null,
            ]),
            'rate' => $this->rate,
            'body' => $this->body,
            'type' => $this->type,
            'audio' => $this->when($this->resource->hasMedia('audio_review'), $this->resource->getFirstMediaUrl('audio_review')),
            'photo' => $this->when($this->resource->hasMedia('photo_review'), $this->resource->getFirstMediaUrl('photo_review')),
            'up_votes' => $this->up_votes,
            'down_votes' => $this->down_votes,
            
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
