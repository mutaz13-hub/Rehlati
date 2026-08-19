<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Cache;

class Tag extends Model
{
    public const MORPH_KEY = 'tag';

    protected $fillable = [
        'name_en',
        'name_ar',
    ];

    public function getLocalizedNameAttribute(): string
    {
        $locale = Cache::get('lang_for_user: '.auth()->id(), app()->getLocale());

        return $this->{"name_{$locale}"} ?? $this->name_en;
    }

    public function taggables(): MorphToMany
    {
        return $this->morphedByMany(Model::class, 'taggable');
    }

    public function cities(): MorphToMany
    {
        return $this->morphedByMany(City::class, 'taggable');
    }

    public function regions(): MorphToMany
    {
        return $this->morphedByMany(Region::class, 'taggable');
    }
}
