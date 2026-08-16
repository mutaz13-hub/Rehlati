<?php

namespace App\Http\Requests\Community;

use App\Enums\CommunityMemberStatus;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class MembersIndexRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in([CommunityMemberStatus::APPROVED->value, CommunityMemberStatus::PENDING->value])],
            'page' => ['nullable', 'integer'],
        ];
    }
}
