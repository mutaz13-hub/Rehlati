<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class BedType extends Model
{
    protected $fillable = [
        'name_en',
        'name_ar',
        'default_capacity',
    ];

    protected function casts(): array
    {
        return [
            'default_capacity' => 'integer',
        ];
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = Cache::get('lang_for_user: '.auth()->id(), app()->getLocale());

        return $this->{"name_{$locale}"} ?? $this->name_en;
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_bed')
            ->withPivot('quantity', 'assigned_capacity')
            ->withTimestamps();
    }
}
