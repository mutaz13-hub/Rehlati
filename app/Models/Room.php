<?php

namespace App\Models;

use App\Enums\RoomClass;
use App\Enums\RoomLayout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Room extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'hotel_id',
        'name_en',
        'name_ar',
        'room_class',
        'room_layout',
        'max_adults',
        'max_children',
        'max_guests',
        'room_type',
        'bed_type',
        'total_rooms',
        'available_rooms',
    ];

    protected function casts(): array
    {
        return [
            'room_class' => RoomClass::class,
            'room_layout' => RoomLayout::class,
            'max_adults' => 'integer',
            'max_children' => 'integer',
            'max_guests' => 'integer',
            'total_rooms' => 'integer',
            'available_rooms' => 'integer',
        ];
    }

    public const MORPH_KEY = 'room';

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function description(): MorphOne
    {
        return $this->morphOne(Description::class, 'describable');
    }

    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'room_amenities')
            ->withTimestamps();
    }

    public function bedTypes(): BelongsToMany
    {
        return $this->belongsToMany(BedType::class, 'room_bed')
            ->withPivot('quantity', 'assigned_capacity')
            ->withTimestamps();
    }

    public function getTotalBedCapacityAttribute(): int
    {
        return $this->bedTypes->sum(function ($bedType) {
            return $bedType->pivot->quantity * $bedType->pivot->assigned_capacity;
        });
    }

    public function getTotalBedsCountAttribute(): int
    {
        return $this->bedTypes->sum(fn ($bedType) => $bedType->pivot->quantity);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('room_pictures');
    }
}
