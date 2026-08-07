<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class City extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    public const MORPH_KEY = 'city';

    protected $fillable = [
        'name_en',
        'name_ar',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = Cache::get('lang_for_user: '.auth()->id(), app()->getLocale());

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

    public function location(): MorphOne
    {
        return $this->morphOne(Location::class, 'locatable');
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
            ->withCount('upVotes')
            ->orderByDesc('up_votes_count')
            ->orderByDesc('rate');
    }

    public function topRegions(): HasMany
    {
        return $this->regions()->with('tags')->withCount('tags')->orderByDesc('tags_count');
    }

    public function top_hotels(): HasMany
    {
        return $this->hotels()->with(['description', 'amenities', 'location'])->orderByDesc('stars');
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->avg('rate') ?? 0, 2);
    }

    public function getTotalReviewsAttribute(): int
    {
        return $this->reviews()->count();
    }

    public function getCanReviewAttribute(): bool
    {
        if ($this->myReview()->exists()) {
            return false;
        }

        return true;
    }

    public function myReview()
    {
        return $this->morphOne(Rating::class, 'rateable')->where('user_id', auth('sanctum')->id());
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function packages(): MorphToMany
    {
        return $this->morphToMany(Package::class, 'packageable');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('city_pictures');
    }
}
