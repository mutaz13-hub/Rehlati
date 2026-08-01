<?php

namespace App\Http\Requests\Admin\Tag;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('tags', 'name_en')->ignore($this->tag->id)],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('tags', 'name_ar')->ignore($this->tag->id)],
        ];
    }
}
