<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DocumentUploadService
{
    public function addUploaded(HasMedia $model, UploadedFile $file, string $collection): Media
    {
        return $model->addMedia($file)
            ->toMediaCollection($collection);
    }
}
