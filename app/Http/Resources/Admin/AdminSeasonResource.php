<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminSeasonResource extends JsonResource
{
    public function toArray($request): array
    {
        $request_type = $request->routeIs('admin.seasons.index') ? 'index' : ($request->routeIs('admin.seasons.show') ? 'show' : 'else');
        return [
            'id' => $this->id,
            'name' => $this->when($request_type !== 'show', $this->localized_name),
            'name_en' => $this->when($request_type === 'show', $this->name_en),
            'name_ar' => $this->when($request_type === 'show', $this->name_ar),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'seasonable_type' => $this->seasonable_type,
            'seasonable_id' => $this->seasonable_id,
            //'prices_count' => $this->when(isset($this->prices_count), $this->prices_count),
        ];
    }
}
