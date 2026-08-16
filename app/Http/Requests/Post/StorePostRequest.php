<?php

namespace App\Http\Requests\Post;

use App\Enums\PostType;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(PostType::values())],
            'body' => [
                Rule::requiredIf(fn () => $this->type === PostType::TEXT->value),
                Rule::prohibitedIf(fn () => $this->type === PostType::AUDIO->value),
                'nullable', 'string', 'max:1000',
            ],
            'pictures' => [
                'array',
                Rule::requiredIf(fn () => $this->type === PostType::IMAGE->value),
                Rule::prohibitedIf(fn () => $this->type !== PostType::IMAGE->value),
            ],
            'pictures.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'video' => [
                'file',
                Rule::requiredIf(fn () => $this->type === PostType::VIDEO->value),
                Rule::prohibitedIf(fn () => $this->type !== PostType::VIDEO->value),
                'mimes:mp4,mov,webm,mkv,avi', 'max:204800',
            ],
            'audio' => [
                'file',
                Rule::requiredIf(fn () => $this->type === PostType::AUDIO->value),
                Rule::prohibitedIf(fn () => $this->type !== PostType::AUDIO->value),
                'mimes:mp3,wav,aac,m4a,ogg', 'max:20480',
            ],
        ];
    }
}
