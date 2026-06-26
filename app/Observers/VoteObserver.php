<?php

namespace App\Observers;

use App\Enums\VoteType;
use App\Models\Vote;
use App\Models\VoteTotal;
use Illuminate\Support\Facades\DB;

class VoteObserver
{
    public function created(Vote $vote): void
    {
        $this->updateVoteTotal($vote->voteable_type, $vote->voteable_id, $vote->vote, 1);
    }

    public function deleted(Vote $vote): void
    {
        $this->updateVoteTotal($vote->voteable_type, $vote->voteable_id, $vote->vote, -1);
    }

    protected function updateVoteTotal(string $voteableType, int $voteableId, VoteType $voteType, int $delta): void
    {
        DB::transaction(function () use ($voteableType, $voteableId, $voteType, $delta) {
            VoteTotal::query()
                ->firstOrCreate([
                    'voteable_type' => $voteableType,
                    'voteable_id' => $voteableId,
                    'vote_type' => $voteType,
                ], [
                    'count' => 0,
                ])
                ->increment('count', $delta);
        });
    }
}
