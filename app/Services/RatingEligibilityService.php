<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\GuideRequestStatus;
use App\Enums\TripInvitationStatus;
use App\Enums\TripStatus;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Package;
use App\Models\Room;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RatingEligibilityService
{
    public function canRate(User $user, Model $rateable): bool
    {
        return $this->hasFinishedTripFor($user, $rateable)
            || $this->hasConfirmedBookingFor($user, $rateable);
    }

    private function hasFinishedTripFor(User $user, Model $rateable): bool
    {
        $finishedTrips = Trip::query()
            ->where('status', TripStatus::FINISHED)
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', function ($m) use ($user) {
                        $m->where('trip_members.user_id', $user->id)
                            ->where('trip_members.status', TripInvitationStatus::APPROVED);
                    });
            });

        return match ($rateable) {
            Hotel => (clone $finishedTrips)->whereHas('destinations', function ($d) use ($rateable) {
                $d->where(function ($q) use ($rateable) {
                    $q->where('destinable_type', Hotel::MORPH_KEY)
                        ->where('destinable_id', $rateable->id);
                })->orWhere(function ($q) use ($rateable) {
                    $q->where('destinable_type', Room::MORPH_KEY)
                        ->whereHas('destinable', function ($r) use ($rateable) {
                            $r->where('hotel_id', $rateable->id);
                        });
                });
            })->exists(),

            Room => (clone $finishedTrips)->whereHas('destinations', function ($d) use ($rateable) {
                $d->where('destinable_type', Room::MORPH_KEY)
                    ->where('destinable_id', $rateable->id);
            })->exists(),

            City => (clone $finishedTrips)->whereHas('cities', function ($c) use ($rateable) {
                $c->where('city_id', $rateable->id);
            })->exists(),

            Region => (clone $finishedTrips)->whereHas('cities', function ($c) use ($rateable) {
                $c->whereHas('city', function ($city) use ($rateable) {
                    $city->whereHas('regions', function ($r) use ($rateable) {
                        $r->where('regions.id', $rateable->id);
                    });
                });
            })->exists(),

            TouristGuide => (clone $finishedTrips)->whereHas('guideRequests', function ($g) use ($rateable) {
                $g->where('tourist_guide_id', $rateable->id)
                    ->where('status', GuideRequestStatus::APPROVED);
            })->exists(),

            default => false,
        };
    }

    private function hasConfirmedBookingFor(User $user, Model $rateable): bool
    {
        $confirmedBookings = Booking::query()
            ->where('user_id', $user->id)
            ->where('status', BookingStatus::CONFIRMED);

        return match ($rateable) {
            Room => (clone $confirmedBookings)
                ->where('bookable_type', Room::MORPH_KEY)
                ->where('bookable_id', $rateable->id)
                ->exists(),

            Hotel => (clone $confirmedBookings)->where(function ($q) use ($rateable) {
                $q->where('bookable_type', Room::MORPH_KEY)
                    ->whereHas('bookable', function ($r) use ($rateable) {
                        $r->where('hotel_id', $rateable->id);
                    });
            })->orWhere(function ($q) use ($rateable) {
                $q->where('bookable_type', Package::MORPH_KEY)
                    ->whereHas('bookable', function ($p) use ($rateable) {
                        $p->whereHas('hotels', function ($h) use ($rateable) {
                            $h->where('hotels.id', $rateable->id);
                        });
                    });
            })->exists(),

            City => (clone $confirmedBookings)
                ->where('bookable_type', Package::MORPH_KEY)
                ->whereHas('bookable', function ($p) use ($rateable) {
                    $p->whereHas('cities', function ($c) use ($rateable) {
                        $c->where('cities.id', $rateable->id);
                    });
                })->exists(),

            Region => (clone $confirmedBookings)
                ->where('bookable_type', Package::MORPH_KEY)
                ->whereHas('bookable', function ($p) use ($rateable) {
                    $p->whereHas('regions', function ($r) use ($rateable) {
                        $r->where('regions.id', $rateable->id);
                    });
                })->exists(),

            TouristGuide => (clone $confirmedBookings)
                ->where('bookable_type', Package::MORPH_KEY)
                ->whereHas('bookable', function ($p) use ($rateable) {
                    $p->whereHas('touristGuides', function ($g) use ($rateable) {
                        $g->where('tourist_guides.id', $rateable->id);
                    });
                })->exists(),

            CarAgency => (clone $confirmedBookings)
                ->where('bookable_type', Package::MORPH_KEY)
                ->whereHas('bookable', function ($p) use ($rateable) {
                    $p->whereHas('carAgencies', function ($a) use ($rateable) {
                        $a->where('car_agencies.id', $rateable->id);
                    });
                })->exists(),

            default => false,
        };
    }
}
