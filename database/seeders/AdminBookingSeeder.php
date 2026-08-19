<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\NationalityCategory;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\Package;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminBookingSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();
        $rooms = Room::all();
        $packages = Package::all();

        if (! $admin || ($rooms->isEmpty() && $packages->isEmpty())) {
            return;
        }

        $nationalities = NationalityCategory::values();
        $guestTypes = ['adult', 'child'];
        $currencies = ['USD', 'SYP'];

        for ($i = 0; $i < 5; $i++) {
            $status = fake()->randomElement([BookingStatus::CONFIRMED, BookingStatus::PENDING]);

            if ($rooms->isNotEmpty() && ($packages->isEmpty() || fake()->boolean(60))) {
                $room = $rooms->random();
                $checkIn = fake()->dateTimeBetween('+3 days', '+60 days');
                $checkOut = (clone $checkIn)->modify('+'.random_int(1, 5).' days');
                $nights = (int) $checkIn->diff($checkOut)->days;
                $guestsCount = random_int(1, min(3, $room->max_guests));
                $totalPrice = round($room->prices()->where('price_type', 'base_price')->first()->amount * $nights, 2);

                $booking = Booking::create([
                    'user_id' => $admin->id,
                    'bookable_type' => Room::MORPH_KEY,
                    'bookable_id' => $room->id,
                    'booking_reference' => (string) Str::uuid(),
                    'status' => $status,
                    'check_in' => $checkIn->format('Y-m-d'),
                    'check_out' => $checkOut->format('Y-m-d'),
                    'guests_count' => $guestsCount,
                    'total_price' => $totalPrice,
                    'currency' => $currencies[array_rand($currencies)],
                    'note' => 'Admin-created booking',
                    'payment_method' => 'stripe',
                    'payment_intent_id' => fake()->uuid(),
                    'payment_status' => PaymentStatus::SUCCEEDED,
                ]);
            } else {
                $package = $packages->random();
                $guestsCount = random_int(1, 4);

                $booking = Booking::create([
                    'user_id' => $admin->id,
                    'bookable_type' => Package::MORPH_KEY,
                    'bookable_id' => $package->id,
                    'booking_reference' => (string) Str::uuid(),
                    'status' => $status,
                    'check_in' => null,
                    'check_out' => null,
                    'guests_count' => $guestsCount,
                    'total_price' => $package->price,
                    'currency' => $package->currency,
                    'note' => 'Admin-created booking',
                    'payment_method' => 'stripe',
                    'payment_intent_id' => fake()->uuid(),
                    'payment_status' => PaymentStatus::SUCCEEDED,
                ]);
            }

            for ($g = 0; $g < $guestsCount; $g++) {
                BookingGuest::create([
                    'booking_id' => $booking->id,
                    'full_name' => fake()->name(),
                    'nationality' => $nationalities[array_rand($nationalities)],
                    'type' => $guestTypes[array_rand($guestTypes)],
                    'national_id' => fake()->optional(0.8)->bothify('??#####'),
                ]);
            }
        }
    }
}
