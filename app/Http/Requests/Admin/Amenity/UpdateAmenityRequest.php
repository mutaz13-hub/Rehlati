<?php

namespace App\Http\Requests\Admin\Amenity;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateAmenityRequest extends ApiFormRequest
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
        ];
    }
}
