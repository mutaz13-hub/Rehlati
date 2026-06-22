<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class City extends Model implements HasMedia
{
    use InteractsWithMedia;
    
    public const MORPH_KEY = 'city';
    protected $fillable = [
        'name_en',
        'name_ar',
    ];

    public function getLocalizedNameAttribute(): string
    {
        $locale = Cache::get('lang_for_user: ' . auth()->id(), app()->getLocale());
        
        return $this->{"name_{$locale}"} ?? $this->name_en;
    }

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class);
    }

    public function description(): MorphOne
    {
        return $this->morphOne(Description::class, 'describable');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function topReviews(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable')
            ->orderByDesc('rate')
            ->orderByDesc('up_votes')
            ->limit(3);
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->avg('rate') ?? 0, 1);
    }

    public function getTotalReviewsAttribute(): int
    {
        return $this->reviews()->count();
    }

    public function getCanReviewAttribute(): bool
    {
        return true;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('city_pictures');
    }
}
