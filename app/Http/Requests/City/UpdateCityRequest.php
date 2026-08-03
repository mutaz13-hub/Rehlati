<?php

namespace App\Http\Requests\City;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\City;
use App\Models\Location;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateCityRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('update', [City::class, $this->route('city')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $cityId = $this->route('city')->id;
        $city = $this->route('city');
        $ownLocationId = $city->location?->id;

        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('cities', 'name_en')->ignore($cityId)],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('cities', 'name_ar')->ignore($cityId)],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'description_ar' => ['nullable', 'string', 'max:10000'],
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
        ];
    }
}
