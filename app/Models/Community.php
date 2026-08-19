<?php

namespace App\Models;

use App\Enums\CommunityMemberRole;
use App\Enums\CommunityMemberStatus;
use App\Enums\CommunityVisibility;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Community extends Model implements HasMedia
{
    use HasFactory, HasUuid, InteractsWithMedia;

    public const MORPH_KEY = 'community';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'visibility',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => CommunityVisibility::class,
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function memberPivots(): HasMany
    {
        return $this->hasMany(CommunityMember::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_members')
            ->withPivot('role', 'status')
            ->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CommunityMessage::class);
    }

    public function isPublic(): bool
    {
        return $this->visibility === CommunityVisibility::PUBLIC;
    }

    public function membershipFor(?User $user): ?CommunityMember
    {
        if (! $user) {
            return null;
        }

        if ($this->relationLoaded('memberPivots')) {
            return $this->memberPivots->firstWhere('user_id', $user->id);
        }

        return $this->memberPivots()->where('user_id', $user->id)->first();
    }

    public function roleFor(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($this->owner_id === $user->id) {
            return CommunityMemberRole::OWNER->value;
        }

        return $this->membershipFor($user)?->role?->value;
    }

    public function isMember(User $user): bool
    {
        return $this->membershipFor($user)?->status === CommunityMemberStatus::APPROVED;
    }

    public function canViewPosts(?User $user): bool
    {
        return $this->isPublic() || $this->isMember($user);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('community_covers')->singleFile();
    }
}
