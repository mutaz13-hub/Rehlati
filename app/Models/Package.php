<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Package extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    public const MORPH_KEY = 'package';

    protected $fillable = [
        'name_en',
        'name_ar',
        'start_date',
        'end_date',
        'duration_days',
        'price',
        'currency',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_days' => 'integer',
            'price' => 'decimal:2',
            'status' => Status::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('package_pictures');
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = Cache::get('lang_for_user: '.auth()->id(), app()->getLocale());

        return $this->{"name_{$locale}"} ?? $this->name_en;
    }

    public function description(): MorphOne
    {
        return $this->morphOne(Description::class, 'describable');
    }

    public function regions(): MorphToMany
    {
        return $this->morphedByMany(Region::class, 'packageable');
    }

    public function cities(): MorphToMany
    {
        return $this->morphedByMany(City::class, 'packageable');
    }

    public function hotels(): MorphToMany
    {
        return $this->morphedByMany(Hotel::class, 'packageable');
    }

    public function carAgencies(): MorphToMany
    {
        return $this->morphedByMany(CarAgency::class, 'packageable');
    }

    public function touristGuides(): MorphToMany
    {
        return $this->morphedByMany(TouristGuide::class, 'packageable');
    }

    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'priceable');
    }
}
