<?php

namespace App\Http\Requests\Trip;

use App\Http\Requests\Api\ApiFormRequest;

class StoreTripRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
        ];
    }
}
