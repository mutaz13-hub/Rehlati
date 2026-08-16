<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TripNote extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const MORPH_KEY = 'trip_note';

    protected $fillable = [
        'trip_id',
        'coordinates',
        'latitude',
        'longitude',
        'description',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('trip_note_pictures');
    }
}
