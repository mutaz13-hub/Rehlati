<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImageUploadService
{
    public function addUploaded(HasMedia $model, UploadedFile $file, string $collection): Media
    {
        return $this->add($model, $file->get(), $file->getClientOriginalName(), $collection);
    }

    public function addFromUrl(HasMedia $model, string $url, string $collection): Media
    {
        return $this->add(
            $model,
            Http::timeout(15)->get($url)->throw()->body(),
            basename(parse_url($url, PHP_URL_PATH) ?: 'image'),
            $collection
        );
    }

    private function add(HasMedia $model, string $contents, string $originalName, string $collection): Media
    {
        $webp = Image::decode($contents)
            ->encode(new WebpEncoder(quality: 60, strip: true));

        $name = pathinfo($originalName, PATHINFO_FILENAME) ?: 'image';

        return $model->addMediaFromString((string) $webp)
            ->usingName($name)
            ->usingFileName($name.'.webp')
            ->toMediaCollection($collection);
    }
}
