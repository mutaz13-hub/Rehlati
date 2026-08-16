<?php

namespace App\Http\Requests\Trip;

use App\Http\Requests\Api\ApiFormRequest;

class StoreLocationPingRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'min:-90', 'max:90'],
            'longitude' => ['required', 'numeric', 'min:-180', 'max:180'],
        ];
    }
}
