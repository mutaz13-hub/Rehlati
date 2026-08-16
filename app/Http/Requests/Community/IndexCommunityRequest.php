<?php

namespace App\Http\Requests\Community;

use App\Http\Requests\Api\ApiFormRequest;

class IndexCommunityRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer'],
        ];
    }
}
