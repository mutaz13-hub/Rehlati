<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Price extends Model
{
    use HasFactory;

    protected $fillable = [
        'priceable_id',
        'priceable_type',
        'price_type',
        'nationality_category',
        'currency',
        'amount',
        'season_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class)->orderBy('start_date', 'desc');
    }

    public function active_season(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'season_id')->where('start_date', '<=', now())->where('end_date', '>=', now());
    }

    public function matchesSeason(Model $season): bool
    {
        return $season->exists && $this->season_id !== null && $this->season_id === $season->getKey();
    }
}
