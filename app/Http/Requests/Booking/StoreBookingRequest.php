<?php

namespace App\Http\Requests\Booking;

use App\Enums\NationalityCategory;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'check_in' => ['nullable', 'date_format:Y-m-d'],
            'check_out' => ['nullable', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:1000'],
            'guests' => ['required', 'array', 'min:1', 'max:50'],
            'guests.*.full_name' => ['required', 'string', 'max:255'],
            'guests.*.nationality' => ['required', Rule::in(NationalityCategory::values())],
            'guests.*.type' => ['required', 'string', 'in:adult,child'],
            'guests.*.national_id' => ['nullable', 'string', 'max:255'],
            'guests.*.id_file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,webp,heic'],
        ];
    }
}
