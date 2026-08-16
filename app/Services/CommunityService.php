<?php

namespace App\Services;

use App\Enums\CommunityMemberRole;
use App\Enums\CommunityMemberStatus;
use App\Enums\CommunityVisibility;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommunityService
{
    public function __construct(private readonly ImageUploadService $imageUploadService) {}

    public function index(?User $user, array $options = []): LengthAwarePaginator
    {
        return Community::query()
            ->with(['owner:id,name'])
            ->with(['memberPivots' => fn ($query) => $query->where('user_id', $user?->id)])
            ->withCount(['memberPivots as members_count' => fn ($query) => $query->where('status', CommunityMemberStatus::APPROVED->value)])
            ->withCount('posts')
            ->when(! empty($options['search']), fn ($query) => $query->where('name', 'like', '%'.$options['search'].'%'))
            ->latest()
            ->paginate(10);
    }

    public function show(Community $community): Community
    {
        return $community
            ->load(['owner:id,name'])
            ->load(['memberPivots' => fn ($query) => $query->where('user_id', auth('sanctum')->id())])
            ->loadCount(['posts', 'memberPivots as members_count' => fn ($query) => $query->where('status', CommunityMemberStatus::APPROVED->value)]);
    }

    public function store(User $user, array $data, ?UploadedFile $cover = null): Community
    {
        return DB::transaction(function () use ($user, $data, $cover) {
            $community = Community::create([
                'name' => $data['name'],
                'visibility' => $data['visibility'] ?? CommunityVisibility::PUBLIC->value,
                'owner_id' => $user->id,
            ]);

            $community->memberPivots()->create([
                'user_id' => $user->id,
                'role' => CommunityMemberRole::OWNER->value,
                'status' => CommunityMemberStatus::APPROVED->value,
                'joined_at' => now(),
            ]);

            if ($cover) {
                $this->imageUploadService->addUploaded($community, $cover, 'community_covers');
            }

            return $community;
        });
    }

    public function update(Community $community, array $data, ?UploadedFile $cover = null, bool $deleteCover = false): void
    {
        DB::transaction(function () use ($community, $data, $cover, $deleteCover) {
            $community->update(array_filter([
                'name' => $data['name'] ?? null,
                'visibility' => $data['visibility'] ?? null,
            ], fn ($value) => $value !== null));

            if ($cover) {
                $community->clearMediaCollection('community_covers');
                $this->imageUploadService->addUploaded($community, $cover, 'community_covers');
            } elseif ($deleteCover) {
                $community->clearMediaCollection('community_covers');
            }
        });
    }

    public function destroy(Community $community): void
    {
        DB::transaction(function () use ($community) {
            $community->posts()->get()->each->delete();
            $community->memberPivots()->delete();
            $community->clearMediaCollection('community_covers');
            $community->delete();
        });
    }

    public function join(Community $community, User $user): void
    {
        $existing = $community->membershipFor($user);

        if ($existing && $existing->status === CommunityMemberStatus::APPROVED) {
            throw ValidationException::withMessages([
                'community' => __('You are already a member of this community'),
            ]);
        }

        if ($existing && $existing->status === CommunityMemberStatus::PENDING) {
            throw ValidationException::withMessages([
                'community' => __('Your join request is already pending'),
            ]);
        }

        $community->memberPivots()->updateOrCreate(
            ['community_id' => $community->id, 'user_id' => $user->id],
            [
                'role' => CommunityMemberRole::MEMBER->value,
                'status' => $community->isPublic()
                    ? CommunityMemberStatus::APPROVED->value
                    : CommunityMemberStatus::PENDING->value,
                'joined_at' => $community->isPublic() ? now() : null,
            ]
        );
    }

    public function leave(Community $community, User $user): void
    {
        if ($community->owner_id === $user->id) {
            throw ValidationException::withMessages([
                'community' => __('The community owner cannot leave; delete the community instead'),
            ]);
        }

        $member = $community->membershipFor($user);

        if (! $member || $member->status !== CommunityMemberStatus::APPROVED) {
            throw ValidationException::withMessages([
                'community' => __('You are not a member of this community'),
            ]);
        }

        $member->delete();
    }

    public function members(Community $community, ?string $status = null): LengthAwarePaginator
    {
        return CommunityMember::query()
            ->where('community_id', $community->id)
            ->when($status, fn ($query) => $query->where('status', $status), fn ($query) => $query->where('status', CommunityMemberStatus::APPROVED->value))
            ->with('user:id,name')
            ->latest()
            ->paginate(10);
    }

    public function updateMemberRole(Community $community, User $user, string $role): void
    {
        if ($community->owner_id === $user->id) {
            throw ValidationException::withMessages([
                'user' => __('The community owner role cannot be changed'),
            ]);
        }

        $member = $community->memberPivots()
            ->where('user_id', $user->id)
            ->where('status', CommunityMemberStatus::APPROVED->value)
            ->firstOrFail();

        $member->update(['role' => $role]);
    }

    public function approveMember(Community $community, User $user): void
    {
        $member = $community->memberPivots()->where('user_id', $user->id)->first();

        if (! $member || $member->status !== CommunityMemberStatus::PENDING) {
            throw ValidationException::withMessages([
                'user' => __('No pending join request was found for this user'),
            ]);
        }

        $member->update([
            'status' => CommunityMemberStatus::APPROVED->value,
            'joined_at' => now(),
        ]);
    }

    public function rejectMember(Community $community, User $user): void
    {
        $member = $community->memberPivots()->where('user_id', $user->id)->first();

        if (! $member || $member->status !== CommunityMemberStatus::PENDING) {
            throw ValidationException::withMessages([
                'user' => __('No pending join request was found for this user'),
            ]);
        }

        $member->update(['status' => CommunityMemberStatus::REJECTED->value]);
    }

    public function removeMember(Community $community, CommunityMember $member): void
    {
        if ($member->community_id !== $community->id) {
            abort(404);
        }

        if ($community->owner_id === $member->user_id) {
            throw ValidationException::withMessages([
                'user' => __('The community owner cannot be removed'),
            ]);
        }

        $member->delete();
    }

    public function rotateUuid(Community $community): Community
    {
        $community->update(['uuid' => (string) Str::uuid()]);

        return $community;
    }
}
