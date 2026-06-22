<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Rating extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const MORPH_KEY = 'rating';

    protected $fillable = [
        'user_id',
        'rateable_type',
        'rateable_id',
        'rate',
        'body',
        'type',
        'up_votes',
        'down_votes',
    ];

    public function rateable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
