<?php

namespace App\Models;

use App\Enums\NationalityCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BookingGuest extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    public const MORPH_KEY = 'booking_guest';

    protected $fillable = [
        'booking_id',
        'full_name',
        'nationality',
        'type',
        'national_id',
    ];

    protected function casts(): array
    {
        return [
            'nationality' => NationalityCategory::class,
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('guest_id_documents');
    }
}
