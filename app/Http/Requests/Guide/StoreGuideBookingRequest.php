<?php

namespace App\Http\Requests\Guide;

use App\Http\Requests\Api\ApiFormRequest;

class StoreGuideBookingRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'tourist_guide_id' => ['required', 'integer', 'exists:tourist_guides,id'],
            'hours' => ['required', 'integer', 'min:1', 'max:24'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
