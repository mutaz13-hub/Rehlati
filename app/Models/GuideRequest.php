<?php

namespace App\Models;

use App\Enums\GuideRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuideRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'tourist_guide_id',
        'status',
        'note',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => GuideRequestStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function touristGuide(): BelongsTo
    {
        return $this->belongsTo(TouristGuide::class);
    }
}
