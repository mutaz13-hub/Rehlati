<?php

namespace App\Http\Requests\Community;

use App\Enums\CommunityVisibility;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateCommunityRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['required', Rule::in(CommunityVisibility::values())],
            'cover' => [
                'nullable', 'image', 'max:5120',
                Rule::prohibitedIf(fn () => $this->exists('delete_cover')),
            ],
            'delete_cover' => [
                'nullable', 'boolean',
                Rule::prohibitedIf(fn () => $this->hasFile('cover')),
            ],
        ];
    }
}
