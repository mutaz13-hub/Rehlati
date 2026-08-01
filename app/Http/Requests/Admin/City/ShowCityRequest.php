<?php

namespace App\Http\Requests\Admin\City;

use App\Http\Requests\Api\ApiFormRequest;

class ShowCityRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
