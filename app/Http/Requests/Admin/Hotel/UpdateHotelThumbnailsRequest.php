<?php

namespace App\Http\Requests\Admin\Hotel;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Hotel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHotelThumbnailsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('update', [Hotel::class, $this->route('hotel')]);
    }

    public function rules(): array
    {
        return [
            'deleted' => ['nullable', 'array', 'max:3'],
            'deleted.*' => ['integer', 'exists:media,id'],
            'added' => ['nullable', 'array', 'max:3'],
            'added.*' => ['integer', 'exists:media,id'],
        ];
    }
}
