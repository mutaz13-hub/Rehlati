<?php

namespace App\Services;

use App\Models\Rating;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\VoteType;
use App\Models\{
    City,
    Hotel,
    Room,
    Region,
    CarAgency
};

class RatingService
{
    public function indexByMorph(string $rateableType, int $rateableId, array $options = [])
    {
        $sort = $options['sort'] ?? 'top';

        $base = Rating::where('rateable_type', $rateableType)
            ->where('rateable_id', $rateableId);

        $count =  $base->count();

        $query = $base->with(['user', 'voteTotals']);

        if ($sort === 'latest') {
            $query = $query->latest();
        } else { // top
            $query = $query->leftJoin('vote_totals', function($join){
                $join->on('vote_totals.voteable_id', 'ratings.id')
                     ->where('vote_totals.voteable_type', Rating::MORPH_KEY);
            })->select('ratings.*')->groupBy('ratings.id')->orderByRaw("
             SUM(CASE WHEN vote_totals.vote_type = 'up' THEN vote_totals.count ELSE 0 END) -
             SUM(CASE WHEN vote_totals.vote_type = 'down' THEN vote_totals.count ELSE 0 END) DESC
            ");
        }
       $paginator = $query->paginate(5);

        return $paginator;
    }

    public function store(array $validatedData, string $routeName, int $rateableId, ?UploadedFile $audio = null, ?UploadedFile $photo = null): void
    {
        $alias = match ($routeName) {
            'ratings.cities' => City::MORPH_KEY,
            'ratings.hotels' => Hotel::MORPH_KEY,
            'ratings.rooms' => Room::MORPH_KEY,
            'ratings.regions' => Region::MORPH_KEY,
            'ratings.car_agencies' => CarAgency::MORPH_KEY,
            default => abort(404),
        };

        DB::transaction(function() use ($validatedData, $alias, $rateableId, $photo, $audio){
            $rating = Rating::create([
                'user_id' => auth('sanctum')->id(),
                'rateable_type' => $alias,
                'rateable_id' => $rateableId,
                'rate' => $validatedData['rate'],
                'body' => $validatedData['body'] ?? null,
                'type' => $validatedData['type'],
            ]);

            if ($validatedData['type'] === 'audio' && $audio) {
                $rating->addMedia($audio)->toMediaCollection('audio_review');
            }

            if ($photo) {
                $rating->addMedia($photo)->toMediaCollection('photo_review');
            }
        });
    }

    public function update(Rating $rating, array $data, ?UploadedFile $audio = null, ?UploadedFile $photo = null): Rating
    {
        $updateData = array_filter([
            'rate' => $data['rate'] ?? $rating->rate,
            'body' => $data['body'] ?? $rating->body,
            'type' => $data['type'] ?? $rating->type,
        ], function ($value) {
            return $value !== null;
        });

        // If type changes to audio, set body to null
        if (isset($data['type']) && $data['type'] === 'audio') {
            $updateData['body'] = null;
        }

        if (!empty($updateData) || $audio || $photo || ($data['delete_photo'] ?? false)) {
            $updateData['edited_at'] = now();
        }

        $rating->update($updateData);

        // Handle type change: text -> audio or audio -> text
        if (isset($data['type'])) {
            if ($data['type'] === 'audio') {
                if ($audio) {
                    $rating->clearMediaCollection('audio_review');
                    $rating->addMedia($audio)->toMediaCollection('audio_review');
                }
            } elseif ($data['type'] === 'text') {
                $rating->clearMediaCollection('audio_review');
            }
        } elseif ($audio) {
            // If type didn't change but audio is provided, update it
            $rating->clearMediaCollection('audio_review');
            $rating->addMedia($audio)->toMediaCollection('audio_review');
        }

        // Handle photo
        if ($photo) {
            $rating->clearMediaCollection('photo_review');
            $rating->addMedia($photo)->toMediaCollection('photo_review');
        } elseif ($data['delete_photo'] ?? false) {
            $rating->clearMediaCollection('photo_review');
        }

        return $rating;
    }

    public function destroy(Rating $rating): void
    {
        $rating->clearMediaCollection('ratings');
        $rating->delete();
    }

    public function vote(Rating $rating, VoteType $voteType): void
    {
        DB::transaction(function () use ($rating, $voteType) {
            $userId = auth('sanctum')->id();

            $vote = $rating->votes()->where('user_id', $userId)->first();

            if ($vote) {
                $same = $vote->vote === $voteType;
                $vote->delete();
                if ($same) {
                    return;
                }
            }

            $rating->votes()->create([
                'user_id' => $userId,
                'vote' => $voteType,
            ]);
        });
    }
}
