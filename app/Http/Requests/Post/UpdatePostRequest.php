<?php

namespace App\Http\Requests\Post;

use App\Enums\MediaType;
use App\Enums\PostType;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends ApiFormRequest
{
    use ValidatesPostMedia;

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in([PostType::TEXT->value, PostType::AUDIO->value])],
            'body' => [
                Rule::requiredIf(fn () => $this->type === PostType::TEXT->value),
                Rule::prohibitedIf(fn () => $this->type === PostType::AUDIO->value),
                'nullable', 'string', 'max:1000',
            ],
            'audio' => [
                'file',
                Rule::requiredIf(fn () => $this->type === PostType::AUDIO->value),
                Rule::prohibitedIf(fn () => $this->type === PostType::TEXT->value),
                'mimes:mp3,wav,aac,m4a,ogg', 'max:20480',
            ],
            'media' => [
                'nullable', 'array',
                Rule::prohibitedIf(fn () => $this->boolean('delete_media')),
            ],
            'media.*.type' => ['required', Rule::in(MediaType::values())],
            'media.*.file' => ['required', 'file'],
            'delete_media' => [
                'nullable', 'boolean',
                Rule::prohibitedIf(fn () => $this->has('media')),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateMediaFiles($validator);
    }
}
