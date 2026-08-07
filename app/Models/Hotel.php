<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Hotel extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name_en',
        'name_ar',
        'city_id',
        'stars',
    ];

    public const MORPH_KEY = 'hotel';

    public function getLocalizedNameAttribute(): string
    {
        $locale = Cache::get('lang_for_user: '.auth()->id(), app()->getLocale());

        return $this->{"name_{$locale}"} ?? $this->name_en;
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function description(): MorphOne
    {
        return $this->morphOne(Description::class, 'describable');
    }

    public function amenity_hotels()
    {
        return $this->hasMany(AmenityHotel::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'amenity_hotels');
    }

    public function location()
    {
        return $this->morphOne(Location::class, 'locatable');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->avg('rate') ?? 0, 2);
    }

    public function getTotalReviewsAttribute(): int
    {
        return $this->reviews()->count();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hotel_pictures');
    }

    public function topReviews(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable')
            ->withCount('upVotes')
            ->orderByDesc('up_votes_count')
            ->orderByDesc('rate');
    }

    public function myReview()
    {
        return $this->morphOne(Rating::class, 'rateable')->where('user_id', auth('sanctum')->id());
    }

    public function contactDetails(): MorphOne
    {
        return $this->morphOne(ContactDetails::class, 'contactable');
    }

    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function packages(): MorphToMany
    {
        return $this->morphToMany(Package::class, 'packageable');
    }
}
