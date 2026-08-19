<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'start_date',
        'end_date',
        'seasonable_type',
        'seasonable_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function getLocalizedNameAttribute(): string
    {
        $locale = Cache::get('lang_for_user: '.auth()->id(), app()->getLocale());

        return $this->{"name_{$locale}"} ?? $this->name_en;
    }

    public function seasonable(): MorphTo
    {
        return $this->morphTo();
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    public function isFor(Model $model): bool
    {
        if ($this->seasonable_type === null || $this->seasonable_id === null) {
            return false;
        }

        if ($this->seasonable_type === $model->getMorphClass() && $this->seasonable_id === $model->getKey()) {
            return true;
        }

        $resolvedModel = $this->seasonable_type && class_exists($this->seasonable_type)
            ? $this->seasonable_type
            : Relation::getMorphedModel($this->seasonable_type);

        return $resolvedModel === $model::class
            && $this->seasonable_id === $model->getKey();
    }
}
