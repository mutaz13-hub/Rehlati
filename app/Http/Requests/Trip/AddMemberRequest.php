<?php

namespace App\Http\Requests\Trip;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class AddMemberRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'user_name' => ['required', 'string', Rule::exists('users', 'username')],
        ];
    }
}
