<?php

namespace App\Models;

use App\Enums\BedType;
use App\Enums\RoomType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'room_type',
        'bed_type',
        'price_per_night',
        'total_rooms',
        'available_rooms',
    ];

    protected function casts(): array
    {
        return [
            'room_type' => RoomType::class,
            'bed_type' => BedType::class,
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

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'room_amenities');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('room_pictures');
    }
}
