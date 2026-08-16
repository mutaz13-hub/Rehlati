<?php

namespace App\Http\Requests\Community;

use App\Enums\CommunityMemberRole;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateCommunityMemberRoleRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in([CommunityMemberRole::ADMIN->value, CommunityMemberRole::MEMBER->value])],
        ];
    }
}
