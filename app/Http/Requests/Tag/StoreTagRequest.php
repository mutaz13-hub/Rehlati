<?php

namespace App\Http\Requests\Tag;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('tags', 'name_en')],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('tags', 'name_ar')],
        ];
    }
}
