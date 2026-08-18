<?php

namespace App\Http\Resources;

use App\Enums\MediaType;
use App\Enums\VoteType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PostResource extends JsonResource
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
            'community_id' => $this->community_id,
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'type' => $this->type->value,
            'body' => $this->body,
            'audio' => $this->hasMedia('post_audio') ? $this->getFirstMediaUrl('post_audio') : null,
            'media' => $this->getMedia('post_pictures')
                ->concat($this->getMedia('post_videos'))
                ->sortBy('id')
                ->values()
                ->map(fn (Media $item) => [
                    'id' => $item->id,
                    'type' => $item->collection_name === 'post_videos' ? MediaType::VIDEO->value : MediaType::PICTURE->value,
                    'url' => $item->getUrl(),
                    'name' => $item->name,
                    'is_thumbnail' => (bool) $item->getCustomProperty('is_thumbnail'),
                ]),
            'up_votes' => $this->up_votes_count ?? $this->voteTotals->firstWhere('vote_type', VoteType::UP)?->count ?? 0,
            'down_votes' => $this->down_votes_count ?? $this->voteTotals->firstWhere('vote_type', VoteType::DOWN)?->count ?? 0,
            'my_vote' => $this->when(auth('sanctum')->check(), function () {
                return $this->votes()->where('user_id', auth('sanctum')->id())?->first()?->vote;
            }),
            'comments_count' => $this->whenCounted('comments', fn () => $this->comments_count),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
