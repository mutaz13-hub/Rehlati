<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Amenity extends Model
{
    protected $fillable = [
        'name_en',
        'name_ar',
        'slug'
    ];

    public function getLocalizedNameAttribute(): string
    {
        $locale = Cache::get('lang_for_user: ' . auth()->id(), app()->getLocale());

        return $this->{"name_{$locale}"} ?? $this->name_en;
    }

    public function amenityHotels(): HasMany
    {
        return $this->hasMany(AmenityHotel::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_amenities')
            ->withTimestamps();
    }

    public function hotels(): BelongsToMany
    {
        return $this->belongsToMany(Hotel::class, 'amenity_hotels')
            ->withTimestamps();
    }
}
