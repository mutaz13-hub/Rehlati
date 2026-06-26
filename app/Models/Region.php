<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Region extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const MORPH_KEY = 'region';
    protected $fillable = [
        'name_en',
        'name_ar',
        'city_id',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = Cache::get('lang_for_user: ' . auth()->id(), app()->getLocale());
        
        return $this->{"name_{$locale}"} ?? $this->name_en;
    }

    public function description()
    {
        return $this->morphOne(Description::class, 'describable');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('region_pictures');
    }

    public function myReview()
    {
        return $this->morphOne(Rating::class, 'rateable')->where('user_id', auth('sanctum')->id());
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
