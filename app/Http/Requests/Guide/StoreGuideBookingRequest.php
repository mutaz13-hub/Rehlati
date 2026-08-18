<?php

namespace App\Http\Requests\Guide;

use App\Http\Requests\Api\ApiFormRequest;

class StoreGuideBookingRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'tourist_guide_id' => ['required', 'integer', 'exists:tourist_guides,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
