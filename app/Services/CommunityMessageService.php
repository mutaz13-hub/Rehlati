<?php

namespace App\Services;

use App\Models\Community;
use App\Models\CommunityMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CommunityMessageService
{
    public function index(Community $community, int $perPage = 20): LengthAwarePaginator
    {
        return CommunityMessage::query()
            ->where('community_id', $community->id)
            ->with('user:id,name')
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function store(Community $community, User $user, array $data): CommunityMessage
    {
        return $community->messages()->create([
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);
    }

    public function destroy(Community $community, CommunityMessage $message): void
    {
        if ($message->community_id !== $community->id) {
            abort(404);
        }

        $message->delete();
    }
}
