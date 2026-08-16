<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TripDestination extends Model
{
    protected $fillable = [
        'trip_city_id',
        'destinable_type',
        'destinable_id',
        'order',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }

    public function tripCity(): BelongsTo
    {
        return $this->belongsTo(TripCity::class);
    }

    public function destinable(): MorphTo
    {
        return $this->morphTo();
    }
}
