<?php

namespace App\Http\Requests\Room;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends ApiFormRequest
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
            
            'total_rooms' => ['required', 'integer', 'min:0'],
            'available_rooms' => ['required', 'integer', 'min:0'],

            'prices' => ['required', 'array', 'min:1'],
            'prices.*.price_type' => ['required', 'string', Rule::in(['base_price', 'extra_bed_price', 'package_price'])],
            'prices.*.nationality_category' => ['required', 'string', Rule::in(['syrian', 'expat', 'foreigner'])],
            'prices.*.currency' => ['required', 'string', Rule::in(['SYP', 'USD', 'EUR'])],
            'prices.*.amount' => ['required', 'numeric', 'min:0'],
            'prices.*.season_id' => ['nullable', 'integer', 'exists:seasons,id'],
        ];
    }
}
