<?php

namespace App\Http\Requests\Comment;

use App\Enums\PostType;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateCommentRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(PostType::values())],
            'body' => [
                Rule::prohibitedIf(fn () => $this->type === PostType::AUDIO->value),
                'nullable', 'string', 'max:1000',
            ],
            'pictures' => ['nullable', 'array'],
            'pictures.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,webm,mkv,avi', 'max:204800'],
            'audio' => ['nullable', 'file', 'mimes:mp3,wav,aac,m4a,ogg', 'max:20480'],
            'delete_pictures' => ['nullable', 'boolean'],
        ];
    }
}
