<?php

namespace App\Http\Requests\Post;

use App\Enums\MediaType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

trait ValidatesPostMedia
{
    public function validateMediaFiles(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->file('media') ?? [] as $index => $files) {
                $file = $files['file'] ?? null;
                $type = $this->input("media.$index.type");

                if (! $file || ! in_array($type, MediaType::values(), true)) {
                    continue;
                }

                $rules = $type === MediaType::VIDEO->value
                    ? ['file', 'mimes:mp4,mov,webm,mkv,avi', 'max:204800']
                    : ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'];

                foreach (ValidatorFacade::make(['file' => $file], ['file' => $rules])->errors()->get('file') as $message) {
                    $validator->errors()->add("media.$index.file", $message);
                }
            }
        });
    }
}
