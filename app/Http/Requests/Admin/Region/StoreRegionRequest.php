<?php

namespace App\Http\Requests\Admin\Region;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Location;
use Illuminate\Validation\Rule;

class StoreRegionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('regions', 'name_en')->where('city_id', $this->input('city_id'))],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('regions', 'name_ar')->where('city_id', $this->input('city_id'))],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'longitude' => [
                'nullable',
                'required_with:latitude',
                'numeric',
                'between:-180,180',
                Rule::unique(Location::class, 'longitude')
                    ->where(fn ($q) => $q->where('latitude', $this->input('latitude'))),
            ],
            'latitude' => [
                'nullable',
                'required_with:longitude',
                'numeric',
                'between:-90,90',
                Rule::unique(Location::class, 'latitude')
                    ->where(fn ($q) => $q->where('longitude', $this->input('longitude'))),
            ],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'description_ar' => ['nullable', 'string', 'max:10000'],
            'pics' => ['nullable', 'array'],
            'pics.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }
}
