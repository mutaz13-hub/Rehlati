<?php

namespace App\Http\Requests\Trip;

use App\Enums\TripStatus;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateTripStatusRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string|object>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(TripStatus::transitionableTargets())],
        ];
    }
}
