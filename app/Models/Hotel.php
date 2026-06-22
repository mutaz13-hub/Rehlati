<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Hotel extends Model implements HasMedia
{
    use InteractsWithMedia;
    
    protected $fillable = [
        'name_en',
        'name_ar',
        'city_id',
        'stars',
    ];

    public const MORPH_KEY = 'hotel';

    public function getLocalizedNameAttribute(): string
    {
        $locale = Cache::get('lang_for_user: ' . auth()->id(), app()->getLocale());
        
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

    public function amenityHotels()
    {
        return $this->hasMany(AmenityHotel::class);
    }

    public function location()
    {
        return $this->morphOne(Location::class, 'locatable');
    }
}
