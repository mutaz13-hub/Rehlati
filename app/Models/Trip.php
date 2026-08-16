<?php

namespace App\Models;

use App\Enums\TripStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Trip extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'title',
        'start_date',
        'owner_id',
        'status',
        'route_polyline',
    ];

    protected function casts(): array
    {
        return [
            'status' => TripStatus::class,
            'start_date' => 'date',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(TripCity::class)->orderBy('order');
    }

    public function destinations(): HasManyThrough
    {
        return $this->hasManyThrough(TripDestination::class, TripCity::class, 'trip_id', 'trip_city_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(TripLocation::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TripNote::class);
    }

    public function memberPivots(): HasMany
    {
        return $this->hasMany(TripMember::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'trip_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->status === TripStatus::ACTIVE;
    }

    public function isPreparing(): bool
    {
        return $this->status === TripStatus::PREPARING;
    }

    /**
     * Resolve the caller's access level relative to this trip.
     */
    public function roleFor(?User $user): string
    {
        if (! $user) {
            return 'viewer';
        }

        if ($this->owner_id === $user->id) {
            return 'owner';
        }

        $member = $this->relationLoaded('memberPivots')
            ? $this->memberPivots->firstWhere(fn ($member) => $member->user_id === $user->id && $member->status?->value === 'approved')
            : $this->memberPivots()
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->first();

        return $member?->role?->value ?? 'viewer';
    }

    public function hasMember(User $user): bool
    {
        if ($this->relationLoaded('memberPivots')) {
            return $this->memberPivots->contains(
                fn ($member) => $member->user_id === $user->id && $member->status?->value === 'approved'
            );
        }

        return $this->memberPivots()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();
    }
}
