<?php

namespace App\Services;

use App\Enums\MediaType;
use App\Enums\VoteType;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PostService
{
    public function __construct(private readonly ImageUploadService $imageUploadService) {}

    public function index(Community $community, array $options = []): LengthAwarePaginator
    {
        $sort = $options['sort'] ?? 'top';

        $query = Post::where('community_id', $community->id)
            ->with(['user:id,name', 'voteTotals']);

        if ($sort === 'latest') {
            $query = $query->latest();
        } else { // top
            $query = $query->leftJoin('vote_totals', function ($join) {
                $join->on('vote_totals.voteable_id', 'posts.id')
                    ->where('vote_totals.voteable_type', Post::MORPH_KEY);
            })->select('posts.*')->groupBy('posts.id')->orderByRaw("
                SUM(CASE WHEN vote_totals.vote_type = 'up' THEN vote_totals.count ELSE 0 END) -
                SUM(CASE WHEN vote_totals.vote_type = 'down' THEN vote_totals.count ELSE 0 END) DESC
            ")->orderByDesc('posts.created_at');
        }

        $query->withCount('comments');

        return $query->paginate(10);
    }

    public function show(Post $post): Post
    {
        return $post->load(['user:id,name', 'voteTotals'])->loadCount('comments');
    }

    public function store(Community $community, User $user, array $data, array $media = []): void
    {
        DB::transaction(function () use ($community, $user, $data, $media) {
            $post = Post::create([
                'community_id' => $community->id,
                'user_id' => $user->id,
                'type' => $data['type'],
                'body' => $data['body'] ?? null,
            ]);

            if (! empty($data['audio'])) {
                $post->addMedia($data['audio'])->toMediaCollection('post_audio');
            }

            $this->attachMedia($post, $media);
        });
    }

    public function update(Post $post, array $data, array $media = [], bool $deleteMedia = false): void
    {
        DB::transaction(function () use ($post, $data, $media, $deleteMedia) {
            $updateData = [];

            if (array_key_exists('body', $data)) {
                $updateData['body'] = $data['body'];
            }

            if (isset($data['type']) && $data['type'] !== $post->type->value) {
                $post->clearMediaCollection('post_pictures');
                $post->clearMediaCollection('post_videos');
                $post->clearMediaCollection('post_audio');
                $updateData['type'] = $data['type'];
            }

            if (! empty($updateData)) {
                $post->update($updateData);
            }

            if ($media) {
                $post->clearMediaCollection('post_pictures');
                $post->clearMediaCollection('post_videos');
                $this->attachMedia($post, $media);
            } elseif ($deleteMedia) {
                $post->clearMediaCollection('post_pictures');
                $post->clearMediaCollection('post_videos');
            }

            if (! empty($data['audio'])) {
                $post->clearMediaCollection('post_audio');
                $post->addMedia($data['audio'])->toMediaCollection('post_audio');
            }
        });
    }

    private function attachMedia(Post $post, array $media): void
    {
        foreach ($media as $item) {
            $file = $item['file'] ?? null;

            if (! $file instanceof UploadedFile) {
                continue;
            }

            if (($item['type'] ?? null) === MediaType::PICTURE->value) {
                $this->imageUploadService->addUploaded($post, $file, 'post_pictures');
            } else {
                $post->addMedia($file)->toMediaCollection('post_videos');
            }
        }
    }

    public function destroy(Post $post): void
    {
        $post->delete();
    }

    public function vote(Post $post, VoteType $voteType): void
    {
        DB::transaction(function () use ($post, $voteType) {
            $userId = auth('sanctum')->id();

            $vote = $post->votes()->where('user_id', $userId)->first();

            if ($vote) {
                $same = $vote->vote === $voteType;
                $vote->delete();
                if ($same) {
                    return;
                }
            }

            $post->votes()->create([
                'user_id' => $userId,
                'vote' => $voteType,
            ]);
        });
    }
}
