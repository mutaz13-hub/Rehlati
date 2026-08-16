<?php

namespace App\Models;

use App\Enums\TripInvitationStatus;
use App\Enums\TripMemberRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripMember extends Model
{
    protected $fillable = [
        'trip_id',
        'user_id',
        'role',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => TripMemberRole::class,
            'status' => TripInvitationStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
