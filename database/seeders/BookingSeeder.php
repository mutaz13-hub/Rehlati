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

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereHas('roles', fn ($q) => $q->where('name', 'user'))->get();
        $rooms = Room::all();
        $packages = Package::all();

        if ($users->isEmpty() || ($rooms->isEmpty() && $packages->isEmpty())) {
            return;
        }

        $statuses = BookingStatus::values();
        $currencies = ['USD', 'SYP'];
        $nationalities = NationalityCategory::values();
        $guestTypes = ['adult', 'child'];

        foreach ($users as $user) {
            $numBookings = fake()->numberBetween(1, 3);

            for ($i = 0; $i < $numBookings; $i++) {
                if ($rooms->isNotEmpty() && ($packages->isEmpty() || fake()->boolean(70))) {
                    $room = $rooms->random();
                    $checkIn = fake()->dateTimeBetween('+3 days', '+60 days');
                    $checkOut = (clone $checkIn)->modify('+'.random_int(1, 7).' days');
                    $nights = (int) $checkIn->diff($checkOut)->days;
                    $guestsCount = random_int(1, $room->max_guests);
                    $totalPrice = round($room->prices()->where('price_type', 'base_price')->first()->amount * $nights, 2);

                    $booking = Booking::create([
                        'user_id' => $user->id,
                        'bookable_type' => Room::MORPH_KEY,
                        'bookable_id' => $room->id,
                        'booking_reference' => (string) Str::uuid(),
                        'status' => $statuses[array_rand($statuses)],
                        'check_in' => $checkIn->format('Y-m-d'),
                        'check_out' => $checkOut->format('Y-m-d'),
                        'guests_count' => $guestsCount,
                        'total_price' => $totalPrice,
                        'currency' => $currencies[array_rand($currencies)],
                        'note' => fake()->optional(0.4)->sentence(),
                        'payment_method' => 'stripe',
                        'payment_intent_id' => fake()->uuid(),
                        'payment_status' => PaymentStatus::SUCCEEDED,
                    ]);
                } else {
                    $package = $packages->random();
                    $guestsCount = random_int(1, 4);

                    $booking = Booking::create([
                        'user_id' => $user->id,
                        'bookable_type' => Package::MORPH_KEY,
                        'bookable_id' => $package->id,
                        'booking_reference' => (string) Str::uuid(),
                        'status' => $statuses[array_rand($statuses)],
                        'check_in' => null,
                        'check_out' => null,
                        'guests_count' => $guestsCount,
                        'total_price' => $package->price,
                        'currency' => $package->currency,
                        'note' => fake()->optional(0.4)->sentence(),
                        'payment_method' => 'stripe',
                        'payment_intent_id' => fake()->uuid(),
                        'payment_status' => PaymentStatus::SUCCEEDED,
                    ]);
                }

                $this->createGuests($booking, $guestsCount, $nationalities, $guestTypes);
            }
        }
    }

    private function createGuests(Booking $booking, int $count, array $nationalities, array $guestTypes): void
    {
        for ($i = 0; $i < $count; $i++) {
            BookingGuest::create([
                'booking_id' => $booking->id,
                'full_name' => fake()->name(),
                'nationality' => $nationalities[array_rand($nationalities)],
                'type' => $guestTypes[array_rand($guestTypes)],
                'national_id' => fake()->optional(0.7)->bothify('??#####'),
            ]);
        }
    }
}
