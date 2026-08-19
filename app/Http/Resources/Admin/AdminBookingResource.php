<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\BookingGuestResource;
use App\Models\Package;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_reference' => $this->booking_reference,
            'status' => $this->status?->value,
            'user' => $this->when($this->relationLoaded('user'), fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone_number' => $this->user->phone_number,
            ]),
            'bookable' => $this->whenLoaded('bookable', fn () => $this->bookableSummary()),
            'check_in' => $this->check_in?->toDateString(),
            'check_out' => $this->check_out?->toDateString(),
            'guests_count' => $this->guests_count,
            'total_price' => $this->total_price !== null ? (float) $this->total_price : null,
            'currency' => $this->currency,
            'note' => $this->note,
            'payment' => [
                'method' => $this->payment_method,
                'status' => $this->payment_status?->value,
            ],
            'guests' => BookingGuestResource::collection($this->whenLoaded('guests')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }

    private function bookableSummary(): ?array
    {
        $bookable = $this->bookable;

        if ($bookable instanceof Room || $bookable instanceof Package) {
            return [
                'type' => $this->bookable_type,
                'id' => $bookable->id,
                'name' => $bookable->localized_name,
            ];
        }

        return null;
    }
}
