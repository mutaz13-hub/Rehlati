<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    public function AmenityHotels()
    {
        return $this->hasMany(AmenityHotel::class);
    }
}
