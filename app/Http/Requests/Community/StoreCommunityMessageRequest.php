<?php

namespace App\Http\Requests\Community;

use App\Http\Requests\Api\ApiFormRequest;

class StoreCommunityMessageRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
