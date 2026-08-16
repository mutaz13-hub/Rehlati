<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripCity extends Model
{
    protected $fillable = [
        'trip_id',
        'city_id',
        'order',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(TripDestination::class)->orderBy('order');
    }
}
