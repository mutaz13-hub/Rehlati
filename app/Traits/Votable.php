<?php

namespace App\Traits;

use App\Enums\VoteType;
use App\Models\Vote;
use App\Models\VoteTotal;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait Votable
{
    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'voteable');
    }

    public function voteTotals(): MorphMany
    {
        return $this->morphMany(VoteTotal::class, 'voteable');
    }

    public function voteTotal(VoteType $voteType): MorphOne
    {
        return $this->morphOne(VoteTotal::class, 'voteable')->where('vote_type', $voteType);
    }

    public function upVotes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'voteable')->where('vote', VoteType::UP);
    }

    public function downVotes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'voteable')->where('vote', VoteType::DOWN);
    }

    public function getVoteCount(VoteType $voteType): int
    {
        $voteTotal = $this->voteTotal($voteType)->first();
        return $voteTotal ? $voteTotal->count : 0;
    }

    public function getUpVotesCountAttribute(): int
    {
        return $this->getVoteCount(VoteType::UP);
    }

    public function getDownVotesCountAttribute(): int
    {
        return $this->getVoteCount(VoteType::DOWN);
    }
}
