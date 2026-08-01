<?php

namespace App\Http\Requests\Hotel;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Hotel;
use Illuminate\Validation\Rule;

class StoreHotelRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('create', Hotel::class);
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('hotels', 'name_en')],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('hotels', 'name_ar')],
            'city_id' => ['required', 'numeric', 'integer', 'exists:cities,id'],
            'stars' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:5'],
            'description_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:255'],
            'pics' => ['nullable', 'array'],
            'pics.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }
}
