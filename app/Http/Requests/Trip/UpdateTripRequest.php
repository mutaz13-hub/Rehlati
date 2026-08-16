<?php

namespace App\Http\Requests\Trip;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateTripRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
        ];
    }
}
