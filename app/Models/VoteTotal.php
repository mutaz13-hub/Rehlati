<?php

namespace App\Models;

use App\Enums\VoteType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VoteTotal extends Model
{
    protected $fillable = [
        'voteable_type',
        'voteable_id',
        'vote_type',
        'count',
    ];

    protected $casts = [
        'vote_type' => VoteType::class,
        'count' => 'integer',
    ];

    public function voteable(): MorphTo
    {
        return $this->morphTo();
    }
}
