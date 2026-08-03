<?php

namespace App\Http\Requests\Admin\Hotel;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Hotel;
use App\Models\Location;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;
use Propaganistas\LaravelPhone\Rules\Phone;
use Throwable;

class UpdateHotelRequest extends ApiFormRequest
{

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
        $hotel = $this->route('hotel');
        $hotelId = $hotel->id;
        $ownLocationId = $hotel->location?->id;

        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('hotels', 'name_en')->where('city_id', $this->input('city_id'))->ignore($hotelId)],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('hotels', 'name_ar')->where('city_id', $this->input('city_id'))->ignore($hotelId)],
            'city_id' => 'required|integer|exists:cities,id',
            'stars' => 'required|integer|min:0|max:5',
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
            'phone' => [
                'nullable',
                'required_with:email',
                'string',
                (new Phone)->country('SY'),
                Rule::unique('contact_details', 'phone')->where(function ($q) use ($hotelId) {
                    return $q->whereNot(function ($q) use ($hotelId) {
                        return $q->where('contactable_type', Hotel::MORPH_KEY)
                                ->where('contactable_id', $hotelId);
                    });
                }),
            ],
            'email' => ['nullable', 'required_with:phone', 'string', 'email', 'max:255', Rule::unique('contact_details', 'email')->where(function ($q) use ($hotelId) {
                return $q->whereNot(function ($q) use ($hotelId) {
                    return $q->where('contactable_type', Hotel::MORPH_KEY)
                            ->where('contactable_id', $hotelId);
                });
            })],
            'description_en' => 'nullable|string|max:10000',
            'description_ar' => 'nullable|string|max:10000',
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', 'exists:amenities,id'],
        ];
    }
}
