<?php

namespace App\Http\Requests\Admin\Room;

use App\Enums\RoomClass;
use App\Enums\RoomLayout;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class AdminStoreRoomRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('rooms', 'name_en')->where(function ($query) {
                return $query->where('hotel_id', $this->route('hotel')->id);
            })],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('rooms', 'name_ar')->where(function ($query) {
                return $query->where('hotel_id', $this->route('hotel')->id);
            })],

            'room_class' => ['required', 'string', Rule::enum(RoomClass::class)],
            'room_layout' => ['required', 'string', Rule::enum(RoomLayout::class)],

            'max_adults' => ['required', 'integer', 'min:1'],
            'max_children' => ['required', 'integer', 'min:0'],
            'max_guests' => ['required', 'integer', 'min:1'],

            'room_type' => ['nullable', 'string'],
            'bed_type' => ['nullable', 'string'],

            'total_rooms' => ['required', 'integer', 'min:0'],
            'available_rooms' => ['required', 'integer', 'min:0'],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'description_ar' => ['nullable', 'string', 'max:10000'],

            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', 'exists:amenities,id'],

            'beds' => ['required', 'array', 'min:1'],
            'beds.*.bed_type_id' => ['required', 'integer', 'exists:bed_types,id'],
            'beds.*.quantity' => ['required', 'integer', 'min:1'],
            'beds.*.assigned_capacity' => ['required', 'integer', 'min:1'],

            'prices' => ['required', 'array', 'min:1'],
            'prices.*.price_type' => ['required', 'string', Rule::in(['base_price', 'extra_bed_price', 'package_price'])],
            'prices.*.nationality_category' => ['required', 'string', Rule::in(['syrian', 'expat', 'foreigner'])],
            'prices.*.currency' => ['required', 'string', Rule::in(['SYP', 'USD', 'EUR'])],
            'prices.*.amount' => ['required', 'numeric', 'min:0'],
            'prices.*.season_id' => ['nullable', 'integer', 'exists:seasons,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $maxAdults = (int) $this->input('max_adults', 0);
            $maxChildren = (int) $this->input('max_children', 0);
            $maxGuests = (int) $this->input('max_guests', 0);

            if ($maxGuests < ($maxAdults + $maxChildren)) {
                $validator->errors()->add(
                    'max_guests',
                    __('max_guests must be greater than or equal to max_adults + max_children')
                );
            }

            $available = (int) $this->input('available_rooms', 0);
            $total = (int) $this->input('total_rooms', 0);
            if ($available > $total) {
                $validator->errors()->add(
                    'available_rooms',
                    __('available_rooms cannot exceed total_rooms')
                );
            }

            $beds = $this->input('beds', []);
            $seen = [];
            foreach ($beds as $index => $bed) {
                $id = $bed['bed_type_id'] ?? null;
                if ($id !== null) {
                    if (in_array($id, $seen, true)) {
                        $validator->errors()->add(
                            "beds.{$index}.bed_type_id",
                            __('Duplicate bed_type_id — combine quantities into one entry')
                        );
                    }
                    $seen[] = $id;
                }
            }

            $prices = $this->input('prices', []);
            $priceKeys = [];
            foreach ($prices as $index => $price) {
                $key = implode('|', [
                    $price['price_type'] ?? '',
                    $price['nationality_category'] ?? '',
                    $price['season_id'] ?? 'NULL',
                ]);
                if (isset($priceKeys[$key])) {
                    $validator->errors()->add(
                        "prices.{$index}",
                        __('Duplicate price tier — same price_type, nationality_category, and season_id already provided')
                    );
                }
                $priceKeys[$key] = true;
            }
        });
    }
}
