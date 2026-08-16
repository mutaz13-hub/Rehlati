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
            'name' => ['nullable', 'string', 'max:255'],
            'visibility' => ['nullable', Rule::in(CommunityVisibility::values())],
            'cover' => ['nullable', 'image', 'max:5120'],
            'delete_cover' => ['nullable', 'boolean'],
        ];
    }
}
