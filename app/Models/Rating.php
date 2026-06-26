<?php

namespace App\Models;

use App\Traits\Votable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Rating extends Model implements HasMedia
{
    use InteractsWithMedia, Votable;

    public const MORPH_KEY = 'rating';

    protected $fillable = [
        'user_id',
        'rateable_type',
        'rateable_id',
        'rate',
        'body',
        'type',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
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
