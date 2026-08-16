<?php

namespace App\Http\Requests\Trip;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateTripCityRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string|object>>
     */
    public function rules(): array
    {
        return [
            'cities' => ['required', 'array', 'min:1'],
            'cities.*.city_id' => ['required', 'integer', Rule::exists('cities', 'id')],
            'cities.*.order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
