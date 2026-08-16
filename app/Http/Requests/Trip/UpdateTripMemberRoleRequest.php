<?php

namespace App\Http\Requests\Trip;

use App\Enums\TripMemberRole;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateTripMemberRoleRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(TripMemberRole::values())],
        ];
    }
}
