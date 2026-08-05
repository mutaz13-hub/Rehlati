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
        return $this->belongsTo(Season::class);
    }
}
