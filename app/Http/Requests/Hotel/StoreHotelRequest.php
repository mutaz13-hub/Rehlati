<?php

namespace App\Http\Requests\Hotel;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Hotel;

class StoreHotelRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('create', Hotel::class);
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'city_id' => ['required', 'numeric', 'integer', 'exists:cities,id'],
            'stars' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:5'],
        ];
    }
}
