<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\City;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\CommunityMessage;
use App\Models\Device;
use App\Models\EmergencyNumber;
use App\Models\Hotel;
use App\Models\Location;
use App\Models\Package;
use App\Models\Region;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NewFeaturesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'test-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('app.api_password', bcrypt(self::PASSWORD));
    }

    private function headers(User $user, array $extra = []): array
    {
        $device = Device::create([
            'identifier' => Str::random(60),
            'refresh_token' => Str::random(64),
            'salt' => Str::random(32),
            'token_expires_at' => now()->addDays(30),
        ]);

        $token = $user->createToken('test_token');
        $token->accessToken->forceFill(['device_id' => $device->id])->save();

        return array_merge([
            'Authorization' => 'Bearer '.$token->plainTextToken,
            'Our-Great-Password' => self::PASSWORD,
            'lang' => 'en',
            'device' => $device->identifier,
            'Accept' => 'application/json',
        ], $extra);
    }

    private function user(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_booking_flow_room_and_package_vault_chat_emergency(): void
    {
        $user = $this->user();
        $auth = $this->headers($user);

        $city = City::factory()->create();
        $region = Region::factory()->create(['city_id' => $city->id]);
        Location::create(['latitude' => 33.5, 'longitude' => 36.3, 'locatable_type' => 'region', 'locatable_id' => $region->id]);

        $hotel = Hotel::factory()->create(['city_id' => $city->id]);
        Location::create(['latitude' => 33.5, 'longitude' => 36.3, 'locatable_type' => 'hotel', 'locatable_id' => $hotel->id]);
        $room = Room::create([
            'hotel_id' => $hotel->id,
            'name_en' => 'Suite',
            'name_ar' => 'جناح',
            'total_rooms' => 5,
            'available_rooms' => 5,
        ]);

        $package = Package::factory()->create();
        $package->hotels()->attach($hotel->id);
        $package->regions()->attach($region->id);

        // Room booking with a guest ID file
        $idFile = UploadedFile::fake()->image('id.jpg');
        $response = $this->withHeaders($auth)
            ->postJson("/api/rooms/{$room->id}/bookings", [
                'check_in' => now()->addDays(2)->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
                'guests' => [
                    ['full_name' => 'Test Guest', 'nationality' => 'syrian', 'national_id' => '123', 'id_file' => $idFile],
                ],
            ]);

        $response->assertStatus(201)->assertJsonPath('data.booking.status', 'pending');
        $booking = Booking::first();
        $this->assertSame('room', $booking->bookable_type);
        $this->assertSame(1, BookingGuest::count());
        $this->assertSame(4, $room->fresh()->available_rooms);

        // Package booking
        $resp2 = $this->withHeaders($auth)
            ->postJson("/api/packages/{$package->id}/bookings", [
                'guests' => [
                    ['full_name' => 'Expat Guest', 'nationality' => 'expat', 'id_file' => UploadedFile::fake()->image('passport.jpg')],
                ],
            ]);
        if ($resp2->status() !== 201) {
            fwrite(STDERR, $resp2->getContent().PHP_EOL);
        }
        $resp2->assertStatus(201);
        $this->assertSame(2, Booking::count());

        // My bookings index + show + cancel
        $this->withHeaders($auth)->getJson('/api/bookings')->assertOk();
        $this->withHeaders($auth)->getJson("/api/bookings/{$booking->id}")->assertOk();

        // Vault contains the submitted files
        $vault = $this->withHeaders($auth)->getJson('/api/vault');
        if ($vault->status() !== 200) {
            fwrite(STDERR, $vault->getContent().PHP_EOL);
        }
        $vault->assertOk();
        $this->assertCount(2, $vault->json('data.documents'));

        // Package show exposes map_track for user
        $pkgResponse = $this->withHeaders($auth)->getJson("/api/packages/{$package->id}");
        $pkgResponse->assertOk()
            ->assertJsonStructure(['data' => ['package' => ['map_track']]]);

        // Community chat member-only
        $community = Community::create(['name' => 'Test', 'owner_id' => $user->id, 'visibility' => 'public']);
        CommunityMember::create(['community_id' => $community->id, 'user_id' => $user->id, 'role' => 'owner', 'status' => 'approved']);
        $this->withHeaders($auth)->getJson("/api/communities/{$community->id}/messages")->assertOk();
        $this->withHeaders($auth)
            ->postJson("/api/communities/{$community->id}/messages", ['body' => 'hello chat'])
            ->assertStatus(201);
        $this->assertSame(1, CommunityMessage::count());

        // Emergency numbers (user list + admin CRUD)
        EmergencyNumber::create(['name_en' => 'Police', 'name_ar' => 'الشرطة', 'phone_number' => '112']);
        $this->withHeaders($auth)->getJson('/api/emergency-numbers')->assertOk();
    }

    public function test_admin_flows_and_membership_guards(): void
    {
        $user = $this->user();
        $auth = $this->headers($user);

        $city = City::factory()->create();
        $region = Region::factory()->create(['city_id' => $city->id]);
        Location::create(['latitude' => 33.5, 'longitude' => 36.3, 'locatable_type' => 'region', 'locatable_id' => $region->id]);
        $hotel = Hotel::factory()->create(['city_id' => $city->id]);
        Location::create(['latitude' => 33.6, 'longitude' => 36.2, 'locatable_type' => 'hotel', 'locatable_id' => $hotel->id]);

        $package = Package::factory()->create();
        $package->hotels()->attach($hotel->id);
        $package->regions()->attach($region->id);

        // map_track includes both points with locations
        $pkg = $this->withHeaders($auth)->getJson("/api/packages/{$package->id}")->json('data.package');
        $this->assertCount(2, $pkg['map_track']);
        $types = collect($pkg['map_track'])->pluck('type')->sort()->values()->all();
        $this->assertSame(['hotel', 'region'], $types);

        // A room booking for the admin approval flow
        $room = Room::create(['hotel_id' => $hotel->id, 'name_en' => 'Suite', 'name_ar' => 'جناح', 'total_rooms' => 2, 'available_rooms' => 2]);
        $booking = $this->withHeaders($auth)->postJson("/api/rooms/{$room->id}/bookings", [
            'check_in' => now()->addDays(1)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => [['full_name' => 'Guest', 'nationality' => 'foreigner', 'id_file' => UploadedFile::fake()->image('id.jpg')]],
        ])->json('data.booking');

        $admin = User::factory()->create(['email_verified_at' => now()]);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin->assignRole('admin');
        $this->assertTrue($admin->hasRole('admin'), 'role not on instance');
        $this->assertTrue(User::find($admin->id)->hasRole('admin'), 'role not on fresh instance');
        $this->assertSame('admin', $admin->getRoleNames()->first(), 'role name mismatch');
        $adminAuth = $this->headers($admin);

        // Admin booking index + approve (fresh auth guard so the admin token is resolved)
        app('auth')->forgetGuards();
        $adminIndex = $this->withHeaders($adminAuth)->getJson('/api/admin/bookings');
        $adminIndex->assertOk()
            ->assertJsonCount(1, 'data.bookings');
        $this->withHeaders($adminAuth)->postJson("/api/admin/bookings/{$booking['id']}/approve")->assertOk();
        $this->assertSame(BookingStatus::CONFIRMED->value, Booking::find($booking['id'])->status->value);
        $this->assertSame(1, $room->fresh()->available_rooms);

        // Admin emergency-number CRUD
        $this->withHeaders($adminAuth)->postJson('/api/admin/emergency-numbers', [
            'name_en' => 'Ambulance',
            'name_ar' => 'إسعاف',
            'phone_number' => '110',
        ])->assertStatus(201);
        $this->withHeaders($adminAuth)->getJson('/api/admin/emergency-numbers')->assertOk()
            ->assertJsonCount(1, 'data.emergency_numbers');

        // Community chat is member-only
        $community = Community::create(['name' => 'Private', 'owner_id' => $user->id, 'visibility' => 'public']);
        CommunityMember::create(['community_id' => $community->id, 'user_id' => $user->id, 'role' => 'owner', 'status' => 'approved']);

        $outsider = $this->user();
        $outsiderAuth = $this->headers($outsider);
        app('auth')->forgetGuards();
        $this->withHeaders($outsiderAuth)->getJson("/api/communities/{$community->id}/messages")->assertStatus(403);
        app('auth')->forgetGuards();
        $this->withHeaders($outsiderAuth)->postJson("/api/communities/{$community->id}/messages", ['body' => 'nope'])->assertStatus(403);

        // The member can read the chat back
        app('auth')->forgetGuards();
        $this->withHeaders($auth)->getJson("/api/communities/{$community->id}/messages")->assertOk();
    }
}
