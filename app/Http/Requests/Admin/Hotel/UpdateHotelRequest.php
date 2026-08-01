<?php

namespace App\Http\Requests\Admin\Hotel;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateHotelRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'city_id' => 'required|integer|exists:cities,id',
            'stars' => 'required|integer|min:0|max:5',
            'description_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string|max:255',
            'pics' => 'nullable|array',
            'pics.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }
}
