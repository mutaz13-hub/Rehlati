<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class TouristGuide extends Model
{
    use HasFactory;

    public const MORPH_KEY = 'tourist_guide';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'availability',
        'price_per_hour',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'availability' => 'array',
            'price_per_hour' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function packages(): MorphToMany
    {
        return $this->morphToMany(Package::class, 'packageable');
    }

    public function getAverageRatingAttribute(): float
    {
        if (array_key_exists('reviews_avg_rate', $this->attributes)) {
            return round((float) $this->attributes['reviews_avg_rate'], 2);
        }

        return round($this->reviews()->avg('rate') ?? 0, 2);
    }

    public function getTotalReviewsAttribute(): int
    {
        if (array_key_exists('reviews_count', $this->attributes)) {
            return (int) $this->attributes['reviews_count'];
        }

        return $this->reviews()->count();
    }
}
