<?php

namespace App\Http\Requests\Admin\Region;

use App\Enums\Status;
use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Location;
use Illuminate\Validation\Rule;

class UpdateRegionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $regionId = $this->route('region')->id;
        $region = $this->route('region');
        $ownLocationId = $region->location?->id;

        return [
            'name_en' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('regions', 'name_en')->where('city_id', $this->input('city_id'))->ignore($regionId)],
            'name_ar' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('regions', 'name_ar')->where('city_id', $this->input('city_id'))->ignore($regionId)],
            'city_id' => ['sometimes', 'integer', 'exists:cities,id'],
            'longitude' => [
                'nullable',
                'required_with:latitude',
                'numeric',
                'between:-180,180',
                Rule::unique(Location::class, 'longitude')
                    ->ignore($ownLocationId)
                    ->where(fn ($q) => $q->where('latitude', $this->input('latitude'))),
            ],
            'latitude' => [
                'nullable',
                'required_with:longitude',
                'numeric',
                'between:-90,90',
                Rule::unique(Location::class, 'latitude')
                    ->ignore($ownLocationId)
                    ->where(fn ($q) => $q->where('longitude', $this->input('longitude'))),
            ],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'description_ar' => ['nullable', 'string', 'max:10000'],
            'status' => ['sometimes', 'string', Rule::in(Status::values())],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }
}
