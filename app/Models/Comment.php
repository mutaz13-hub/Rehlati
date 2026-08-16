<?php

namespace App\Models;

use App\Enums\PostType;
use App\Traits\Votable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Comment extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, Votable;

    public const MORPH_KEY = 'comment';

    protected $fillable = [
        'post_id',
        'user_id',
        'type',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('comment_pictures');
        $this->addMediaCollection('comment_videos')->singleFile();
        $this->addMediaCollection('comment_audio')->singleFile();
    }
}
