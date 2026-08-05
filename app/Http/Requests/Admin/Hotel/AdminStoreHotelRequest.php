<?php

namespace App\Http\Requests\Admin\Hotel;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Hotel;
use App\Models\Location;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;
use Propaganistas\LaravelPhone\Rules\Phone;
use Throwable;

class AdminStoreHotelRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('create', Hotel::class);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('phone')) {
            return;
        }

        try {
            $this->merge([
                'phone' => (new PhoneNumber($this->string('phone')->toString(), 'SY'))
                    ->formatE164(),
            ]);
        } catch (Throwable) {
            // Leave the original value as-is so the validator can return the proper error.
        }
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('hotels', 'name_en')->where('city_id', $this->input('city_id'))],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('hotels', 'name_ar')->where('city_id', $this->input('city_id'))],
            'city_id' => ['required', 'numeric', 'integer', 'exists:cities,id'],
            'stars' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:5'],
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
            'phone' => [
                'nullable',
                'required_with:email',
                'string',
                (new Phone)->country('SY'),
                Rule::unique('contact_details', 'phone'),
            ],
            'email' => ['nullable', 'required_with:phone', 'string', 'email', 'max:255', Rule::unique('contact_details', 'email')],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'description_ar' => ['nullable', 'string', 'max:10000'],
            'pics' => ['nullable', 'array'],
            'pics.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', 'exists:amenities,id'],
        ];
    }
}
