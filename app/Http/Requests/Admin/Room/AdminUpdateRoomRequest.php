<?php

namespace App\Http\Requests\Admin\Room;

use App\Enums\RoomClass;
use App\Enums\RoomLayout;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateRoomRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name_en' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('rooms', 'name_en')->where(function ($query) {
                return $query->where('hotel_id', $this->route('hotel')->id);
            })->ignore($this->route('room')->id)],
            'name_ar' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('rooms', 'name_ar')->where(function ($query) {
                return $query->where('hotel_id', $this->route('hotel')->id);
            })->ignore($this->route('room')->id)],

            'room_class' => ['sometimes', 'required', 'string', Rule::enum(RoomClass::class)],
            'room_layout' => ['sometimes', 'required', 'string', Rule::enum(RoomLayout::class)],

            'max_adults' => ['sometimes', 'required', 'integer', 'min:1'],
            'max_children' => ['sometimes', 'required', 'integer', 'min:0'],
            'max_guests' => ['sometimes', 'required', 'integer', 'min:1'],

            'room_type' => ['nullable', 'string'],
            'bed_type' => ['nullable', 'string'],

            'total_rooms' => ['sometimes', 'required', 'integer', 'min:0'],
            'available_rooms' => ['sometimes', 'required', 'integer', 'min:0'],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'description_ar' => ['nullable', 'string', 'max:10000'],

            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', 'exists:amenities,id'],

            'beds' => ['sometimes', 'required', 'array', 'min:1'],
            'beds.*.bed_type_id' => ['required_with:beds', 'integer', 'exists:bed_types,id'],
            'beds.*.quantity' => ['required_with:beds', 'integer', 'min:1'],
            'beds.*.assigned_capacity' => ['required_with:beds', 'integer', 'min:1'],

            'prices' => ['sometimes', 'required', 'array', 'min:1'],
            'prices.*.price_type' => ['required_with:prices', 'string', Rule::in(['base_price', 'extra_bed_price', 'package_price'])],
            'prices.*.nationality_category' => ['required_with:prices', 'string', Rule::in(['syrian', 'expat', 'foreigner'])],
            'prices.*.currency' => ['required_with:prices', 'string', Rule::in(['SYP', 'USD', 'EUR'])],
            'prices.*.amount' => ['required_with:prices', 'numeric', 'min:0'],
            'prices.*.season_id' => ['nullable', 'integer', 'exists:seasons,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $room = $this->route('room');

            $maxAdults = (int) $this->input('max_adults', $room?->max_adults ?? 0);
            $maxChildren = (int) $this->input('max_children', $room?->max_children ?? 0);
            $maxGuests = (int) $this->input('max_guests', $room?->max_guests ?? 0);

            if ($this->filled('max_adults') || $this->filled('max_children') || $this->filled('max_guests')) {
                if ($maxGuests < ($maxAdults + $maxChildren)) {
                    $validator->errors()->add(
                        'max_guests',
                        __('max_guests must be greater than or equal to max_adults + max_children')
                    );
                }
            }

            $available = (int) $this->input('available_rooms', $room?->available_rooms ?? 0);
            $total = (int) $this->input('total_rooms', $room?->total_rooms ?? 0);
            if ($this->filled('available_rooms') || $this->filled('total_rooms')) {
                if ($available > $total) {
                    $validator->errors()->add(
                        'available_rooms',
                        __('available_rooms cannot exceed total_rooms')
                    );
                }
            }

            if ($this->filled('beds')) {
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
            }

            if ($this->filled('prices')) {
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
            }
        });
    }
}
