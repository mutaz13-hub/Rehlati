<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Description extends Model
{
    protected $fillable = [
        'describable_id',
        'describable_type',
        'description_en',
        'description_ar',
    ];

    public function getLocalizedDescriptionAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"description_{$locale}"} ?? $this->description_en;
    }

    public function describable()
    {
        return $this->morphTo();
    }
}
