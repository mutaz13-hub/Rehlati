<?php

namespace App\Http\Requests\Trip;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreTripDestinationRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string|object>>
     */
    public function rules(): array
    {
        return [
            'trip_city_id' => ['required', 'integer', Rule::exists('trip_cities', 'id')],
            'destinations' => ['required', 'array', 'min:1'],
            'destinations.*.type' => ['required', 'string', 'max:255', Rule::in(['hotel', 'region'])],
            'destinations.*.id' => ['required', 'integer', function ($attribute, $value, $fail) {
                $index = (int) explode('.', $attribute)[1];
                $type = $this->input("destinations.{$index}.type");

                $table = $type === 'hotel' ? 'hotels' : 'regions';

                if (! DB::table($table)->where('id', $value)->exists()) {
                    $fail(__('The selected :attribute is invalid.', ['attribute' => $attribute]));
                }
            }],
            'destinations.*.order' => ['nullable', 'integer', 'min:0', 'distinct'],
        ];
    }

    /**
     * Reject the same destination (type + id) being added more than once.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $seen = [];

            foreach ($this->input('destinations', []) as $index => $destination) {
                $type = $destination['type'] ?? null;
                $id = $destination['id'] ?? null;

                if ($type === null || $id === null) {
                    continue;
                }

                $key = $type.'#'.$id;

                if (isset($seen[$key])) {
                    $validator->errors()->add(
                        "destinations.{$index}.id",
                        __('The same destination cannot be added more than once'),
                    );
                }

                $seen[$key] = true;
            }
        });
    }
}
