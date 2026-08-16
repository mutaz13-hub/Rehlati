<?php

namespace App\Services;

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
            ->with(['user:id,name', 'voteTotals'])
            ->withCount('comments');

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

        return $query->paginate(10);
    }

    public function show(Post $post): Post
    {
        return $post->load(['user:id,name', 'voteTotals'])->loadCount('comments');
    }

    public function store(Community $community, User $user, array $data, array $pictures = [], ?UploadedFile $video = null, ?UploadedFile $audio = null): void
    {
        DB::transaction(function () use ($community, $user, $data, $pictures, $video, $audio) {
            $post = Post::create([
                'community_id' => $community->id,
                'user_id' => $user->id,
                'type' => $data['type'],
                'body' => $data['body'] ?? null,
            ]);

            foreach ($pictures as $picture) {
                $this->imageUploadService->addUploaded($post, $picture, 'post_pictures');
            }

            if ($video) {
                $post->addMedia($video)->toMediaCollection('post_videos');
            }

            if ($audio) {
                $post->addMedia($audio)->toMediaCollection('post_audio');
            }
        });
    }

    public function update(Post $post, array $data, array $pictures = [], ?UploadedFile $video = null, ?UploadedFile $audio = null, bool $deletePictures = false): void
    {
        DB::transaction(function () use ($post, $data, $pictures, $video, $audio, $deletePictures) {
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

            if ($pictures) {
                $post->clearMediaCollection('post_pictures');
                foreach ($pictures as $picture) {
                    $this->imageUploadService->addUploaded($post, $picture, 'post_pictures');
                }
            } elseif ($deletePictures) {
                $post->clearMediaCollection('post_pictures');
            }

            if ($video) {
                $post->clearMediaCollection('post_videos');
                $post->addMedia($video)->toMediaCollection('post_videos');
            }

            if ($audio) {
                $post->clearMediaCollection('post_audio');
                $post->addMedia($audio)->toMediaCollection('post_audio');
            }
        });
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
