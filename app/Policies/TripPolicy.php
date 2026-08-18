<?php

namespace App\Policies;

use App\Enums\TripStatus;
use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    private function isGlobalAdmin(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    private function isOwner(User $user, Trip $trip): bool
    {
        return $trip->owner_id === $user->id;
    }

    private function isEditor(User $user, Trip $trip): bool
    {
        if ($this->isOwner($user, $trip)) {
            return true;
        }

        if ($trip->relationLoaded('memberPivots')) {
            return $trip->memberPivots->contains(
                fn ($member) => $member->user_id === $user->id
                    && $member->role?->value === 'editor'
                    && $member->status?->value === 'approved'
            );
        }

        return $trip->memberPivots()
            ->where('user_id', $user->id)
            ->where('role', 'editor')
            ->where('status', 'approved')
            ->exists();
    }

    public function view(?User $user, Trip $trip): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isGlobalAdmin($user) || $this->isOwner($user, $trip)) {
            return true;
        }

        return $trip->hasMember($user);
    }

    public function update(User $user, Trip $trip): bool
    {
        return $this->isGlobalAdmin($user) || $this->isOwner($user, $trip);
    }

    public function updateDestinations(User $user, Trip $trip): bool
    {
        return $this->isGlobalAdmin($user) || $this->isOwner($user, $trip);
    }

    public function manageMembers(User $user, Trip $trip): bool
    {
        return $this->isGlobalAdmin($user) || $this->isOwner($user, $trip);
    }

    public function bookGuide(User $user, Trip $trip): bool
    {
        return $this->isGlobalAdmin($user) || $this->isOwner($user, $trip);
    }

    public function respondToInvitation(User $user, Trip $trip, User $member): bool
    {
        return $user->id === $member->id;
    }

    public function regenerateLink(User $user, Trip $trip): bool
    {
        return $this->isGlobalAdmin($user) || $this->isOwner($user, $trip);
    }

    public function pushLocation(User $user, Trip $trip): bool
    {
        return $this->isGlobalAdmin($user) || $this->isEditor($user, $trip);
    }

    public function createNote(User $user, Trip $trip): bool
    {
        return $this->isGlobalAdmin($user) || $this->isEditor($user, $trip);
    }

    public function changeStatus(User $user, Trip $trip, TripStatus $target): bool
    {
        if ($this->isGlobalAdmin($user)) {
            return true;
        }

        if ($target === TripStatus::FINISHED) {
            return $this->isEditor($user, $trip);
        }

        return $this->isOwner($user, $trip);
    }

    public function destroy(User $user, Trip $trip): bool
    {
        return $this->isGlobalAdmin($user) || $this->isOwner($user, $trip);
    }
}
