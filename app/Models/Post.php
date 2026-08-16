<?php

namespace App\Models;

use App\Enums\PostType;
use App\Traits\Votable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Post extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, Votable;

    public const MORPH_KEY = 'post';

    protected $fillable = [
        'community_id',
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

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('post_pictures');
        $this->addMediaCollection('post_videos')->singleFile();
        $this->addMediaCollection('post_audio')->singleFile();
    }
}
