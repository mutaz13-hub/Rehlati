<?php

namespace App\Services;

use App\Models\Rating;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RatingService
{
    public function indexByMorph(string $rateableType, int $rateableId, array $options = []): LengthAwarePaginator
    {
        $sort = $options['sort'] ?? 'top';

        $query = Rating::with('user')
            ->where('rateable_type', $rateableType)
            ->where('rateable_id', $rateableId);

        if ($sort === 'latest') {
            $query = $query->latest();
        } else { // top
            $query = $query->orderByDesc('up_votes')->orderByDesc('down_votes')->orderByDesc('created_at');
        }

        return $query->paginate(15);
    }

    public function store(array $data, ?UploadedFile $audio = null, ?UploadedFile $photo = null): void
    {
        DB::transaction(function() use ($data, $photo, $audio){
      
        $rating = Rating::create([
            'user_id' => auth('sanctum')->id(),
            'rateable_type' => $data['rateable_type'],
            'rateable_id' => $data['rateable_id'],
            'rate' => $data['rate'],
            'body' => $data['body'] ?? null,
            'type' => $data['type'],
        ]);

        if ($data['type'] === 'audio' && $audio) {
            $rating->addMedia($audio)->toMediaCollection('audio_review');
        }

        if ($photo) {
            $rating->addMedia($photo)->toMediaCollection('photo_review');
        }
          });
    }

    public function update(Rating $rating, array $data, ?UploadedFile $audio = null, ?UploadedFile $photo = null): Rating
    {
        $rating->update(array_filter([
            'rate' => $data['rate'] ?? $rating->rate,
            'body' => $data['body'] ?? $rating->body,
        ]));

        if ($audio) {
            $rating->clearMediaCollection('audio_review');
            $rating->addMedia($audio)->toMediaCollection('audio_review');
        }

        if ($photo) {
            $rating->clearMediaCollection('photo_review');
            $rating->addMedia($photo)->toMediaCollection('photo_review');
        }

        return $rating;
    }

    public function destroy(Rating $rating): void
    {
        $rating->clearMediaCollection('ratings');
        $rating->delete();
    }

    public function vote(Rating $rating, string $vote): void
    {
        if ($vote === 'up') {
            $rating->increment('up_votes');
        } else {
            $rating->increment('down_votes');
        }
    }
}
