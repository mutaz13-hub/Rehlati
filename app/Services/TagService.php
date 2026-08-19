<?php

namespace App\Services;

use App\Models\Tag;

class TagService
{
    public function create(array $data): void
    {
        Tag::create($data);
    }

    public function update(Tag $tag, array $data): void
    {
        $tag->update($data);
    }

    public function delete(Tag $tag): void
    {
        $tag->delete();
    }
}
