<?php

namespace Tests\Feature;

use App\Enums\GuideRequestStatus;
use App\Models\Device;
use App\Models\ExchangeRate;
use App\Models\GuideRequest;
use App\Models\Package;
use App\Models\TouristGuide;
use App\Models\Trip;
use App\Models\User;
use App\Services\AppSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TouristGuideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.api_password' => Hash::make('test-secret')]);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    }

    public function test_admin_can_create_tourist_guide(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->adminUser()))
            ->postJson('/api/admin/tourist-guides', [
                'name' => 'Sami Al-Khatib',
                'email' => 'sami@example.com',
                'phone' => '+963 944 000 001',
                'availability' => ['saturday', 'sunday'],
                'price_per_hour' => 15,
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Tourist guide created successfully']);

        $this->assertDatabaseHas('tourist_guides', [
            'name' => 'Sami Al-Khatib',
            'email' => 'sami@example.com',
            'phone' => '+963 944 000 001',
            'price_per_hour' => 15.00,
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_create_tourist_guide(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->regularUser()))
            ->postJson('/api/admin/tourist-guides', [
                'name' => 'Sami Al-Khatib',
                'email' => 'sami@example.com',
                'phone' => '+963 944 000 001',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseCount('tourist_guides', 0);
    }

    public function test_admin_can_update_tourist_guide(): void
    {
        $guide = TouristGuide::factory()->create(['name' => 'Old Name']);

        $this->withHeaders($this->authHeaders($this->adminUser()))
            ->putJson("/api/admin/tourist-guides/{$guide->id}", [
                'name' => 'New Name',
                'email' => $guide->email,
                'phone' => $guide->phone,
                'availability' => ['monday', 'friday'],
            ])
            ->assertOk()
            ->assertJson(['message' => 'Tourist guide updated successfully']);

        $this->assertDatabaseHas('tourist_guides', [
            'id' => $guide->id,
            'name' => 'New Name',
        ]);
    }

    public function test_admin_can_delete_tourist_guide(): void
    {
        $guide = TouristGuide::factory()->create();

        $this->withHeaders($this->authHeaders($this->adminUser()))
            ->deleteJson("/api/admin/tourist-guides/{$guide->id}")
            ->assertOk()
            ->assertJson(['message' => 'Tourist guide deleted successfully']);

        $this->assertDatabaseCount('tourist_guides', 0);
    }

    public function test_validation_fails_for_invalid_availability_day(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->adminUser()))
            ->postJson('/api/admin/tourist-guides', [
                'name' => 'Bad Guide',
                'email' => 'bad@example.com',
                'phone' => '+963 944 000 099',
                'availability' => ['funday'],
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('tourist_guides', 0);
    }

    public function test_user_can_list_only_active_tourist_guides(): void
    {
        TouristGuide::factory()->count(3)->create(['is_active' => true]);
        TouristGuide::factory()->create(['is_active' => false]);

        $response = $this->withHeaders($this->authHeaders($this->regularUser()))
            ->getJson('/api/tourist-guides');

        $response->assertOk()
            ->assertJsonCount(3, 'data.tourist_guides')
            ->assertJsonMissingPath('data.meta');
    }

    public function test_guide_listing_exposes_price_per_hour(): void
    {
        TouristGuide::factory()->create([
            'name' => 'Lina Haddad',
            'price_per_hour' => 12,
        ]);

        $this->withHeaders($this->authHeaders($this->regularUser()))
            ->getJson('/api/tourist-guides')
            ->assertOk()
            ->assertJsonPath('data.tourist_guides.0.name', 'Lina Haddad')
            ->assertJsonPath('data.tourist_guides.0.price_per_hour', 12)
            ->assertJsonPath('data.tourist_guides.0.currency', 'USD');
    }

    public function test_guide_price_is_converted_to_active_currency(): void
    {
        ExchangeRate::create(['currency' => 'USD', 'rate_to_syp' => 130]);
        app(AppSettingService::class)->set('active_currency', 'SYP');

        TouristGuide::factory()->create(['price_per_hour' => 10]);

        $this->withHeaders($this->authHeaders($this->regularUser()))
            ->getJson('/api/tourist-guides')
            ->assertOk()
            ->assertJsonPath('data.tourist_guides.0.price_per_hour', 1300)
            ->assertJsonPath('data.tourist_guides.0.currency', 'SYP');
    }

    public function test_user_cannot_view_inactive_tourist_guide(): void
    {
        $guide = TouristGuide::factory()->create(['is_active' => false]);

        $this->withHeaders($this->authHeaders($this->regularUser()))
            ->getJson("/api/tourist-guides/{$guide->id}")
            ->assertStatus(404);
    }

    public function test_admin_can_assign_tourist_guides_to_package(): void
    {
        $guide = TouristGuide::factory()->create();

        $this->withHeaders($this->authHeaders($this->adminUser()))
            ->postJson('/api/admin/packages', [
                'name_en' => 'Damascus Discovery',
                'name_ar' => 'اكتشاف دمشق',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-05',
                'status' => 'active',
                'tourist_guides' => [$guide->id],
            ])
            ->assertStatus(201);

        $package = Package::firstOrFail();

        $this->assertDatabaseHas('packageables', [
            'package_id' => $package->id,
            'packageable_id' => $guide->id,
            'packageable_type' => 'tourist_guide',
        ]);
    }

    public function test_user_can_request_guide_booking_on_trip(): void
    {
        $owner = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $guide = TouristGuide::factory()->create();

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/guides", [
                'tourist_guide_id' => $guide->id,
                'note' => 'English speaking guide please.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Guide booking request sent successfully')
            ->assertJsonPath('data.guide_request.status', 'pending');

        $this->assertDatabaseHas('guide_requests', [
            'trip_id' => $trip->id,
            'tourist_guide_id' => $guide->id,
            'status' => GuideRequestStatus::PENDING->value,
            'note' => 'English speaking guide please.',
        ]);
    }

    public function test_duplicate_pending_booking_request_is_rejected(): void
    {
        $owner = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $guide = TouristGuide::factory()->create();

        GuideRequest::factory()->create([
            'trip_id' => $trip->id,
            'tourist_guide_id' => $guide->id,
            'status' => GuideRequestStatus::PENDING->value,
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/guides", [
                'tourist_guide_id' => $guide->id,
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('guide_requests', 1);
    }

    public function test_booking_request_requires_preparing_trip(): void
    {
        $owner = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create(['status' => 'active']);
        $guide = TouristGuide::factory()->create();

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/guides", [
                'tourist_guide_id' => $guide->id,
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('guide_requests', 0);
    }

    public function test_user_can_cancel_pending_booking_request(): void
    {
        $owner = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $guideRequest = GuideRequest::factory()->create([
            'trip_id' => $trip->id,
            'status' => GuideRequestStatus::PENDING->value,
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->deleteJson("/api/trips/{$trip->id}/guides/{$guideRequest->id}")
            ->assertOk()
            ->assertJson(['message' => 'Guide booking request cancelled successfully']);

        $this->assertDatabaseCount('guide_requests', 0);
    }

    public function test_admin_can_approve_guide_request(): void
    {
        $guideRequest = GuideRequest::factory()->create(['status' => GuideRequestStatus::PENDING->value]);

        $this->withHeaders($this->authHeaders($this->adminUser()))
            ->postJson("/api/admin/guide-requests/{$guideRequest->id}/approve")
            ->assertOk()
            ->assertJson(['message' => 'Guide booking request approved successfully']);

        $this->assertDatabaseHas('guide_requests', [
            'id' => $guideRequest->id,
            'status' => GuideRequestStatus::APPROVED->value,
        ]);
        $this->assertNotNull($guideRequest->fresh()->responded_at);
    }

    public function test_admin_can_reject_guide_request(): void
    {
        $guideRequest = GuideRequest::factory()->create(['status' => GuideRequestStatus::PENDING->value]);

        $this->withHeaders($this->authHeaders($this->adminUser()))
            ->postJson("/api/admin/guide-requests/{$guideRequest->id}/reject")
            ->assertOk()
            ->assertJson(['message' => 'Guide booking request rejected successfully']);

        $this->assertDatabaseHas('guide_requests', [
            'id' => $guideRequest->id,
            'status' => GuideRequestStatus::REJECTED->value,
        ]);
    }

    public function test_handled_guide_request_cannot_be_approved_twice(): void
    {
        $guideRequest = GuideRequest::factory()->create(['status' => GuideRequestStatus::APPROVED->value]);

        $this->withHeaders($this->authHeaders($this->adminUser()))
            ->postJson("/api/admin/guide-requests/{$guideRequest->id}/approve")
            ->assertStatus(422);
    }

    public function test_admin_can_list_guide_requests_with_status_filter(): void
    {
        GuideRequest::factory()->count(2)->create(['status' => GuideRequestStatus::PENDING->value]);
        GuideRequest::factory()->create(['status' => GuideRequestStatus::APPROVED->value]);

        $response = $this->withHeaders($this->authHeaders($this->adminUser()))
            ->getJson('/api/admin/guide-requests?status=pending');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    }

    public function test_user_can_rate_tourist_guide(): void
    {
        $user = $this->regularUser();
        $guide = TouristGuide::factory()->create();

        $this->withHeaders($this->authHeaders($user))
            ->postJson("/api/ratings/tourist_guides/{$guide->id}", [
                'rate' => 5,
                'type' => 'text',
                'body' => 'Amazing guide!',
            ])
            ->assertStatus(201)
            ->assertJson(['message' => 'Rating created']);

        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'rateable_type' => 'tourist_guide',
            'rateable_id' => $guide->id,
            'rate' => 5,
        ]);
    }

    public function test_user_cannot_rate_same_tourist_guide_twice(): void
    {
        $user = $this->regularUser();
        $guide = TouristGuide::factory()->create();

        $this->withHeaders($this->authHeaders($user))
            ->postJson("/api/ratings/tourist_guides/{$guide->id}", [
                'rate' => 4,
                'type' => 'text',
                'body' => 'Nice.',
            ])
            ->assertStatus(201);

        $this->withHeaders($this->authHeaders($user))
            ->postJson("/api/ratings/tourist_guides/{$guide->id}", [
                'rate' => 5,
                'type' => 'text',
                'body' => 'Amazing!',
            ])
            ->assertStatus(403);

        $this->assertDatabaseCount('ratings', 1);
    }

    public function test_guide_show_exposes_average_rating(): void
    {
        $user = $this->regularUser();
        $guide = TouristGuide::factory()->create();

        $guide->reviews()->create([
            'user_id' => $user->id,
            'rate' => 4,
            'type' => 'text',
            'body' => 'Good.',
        ]);

        $this->withHeaders($this->authHeaders($this->regularUser()))
            ->getJson("/api/tourist-guides/{$guide->id}")
            ->assertOk()
            ->assertJsonPath('data.tourist_guide.average_rating', 4)
            ->assertJsonPath('data.tourist_guide.total_reviews', 1);
    }

    public function test_admin_show_exposes_price_per_hour(): void
    {
        $guide = TouristGuide::factory()->create(['price_per_hour' => 20]);

        $this->withHeaders($this->authHeaders($this->adminUser()))
            ->getJson("/api/admin/tourist-guides/{$guide->id}")
            ->assertOk()
            ->assertJsonPath('data.tourist_guide.price_per_hour', 20)
            ->assertJsonPath('data.tourist_guide.currency', 'USD')
            ->assertJsonPath('data.tourist_guide.active_currency', 'USD')
            ->assertJsonPath('data.tourist_guide.active_price_per_hour', 20);
    }

    public function test_admin_show_converts_price_to_active_currency(): void
    {
        ExchangeRate::create(['currency' => 'USD', 'rate_to_syp' => 130]);
        app(AppSettingService::class)->set('active_currency', 'SYP');

        $guide = TouristGuide::factory()->create(['price_per_hour' => 20]);

        $this->withHeaders($this->authHeaders($this->adminUser()))
            ->getJson("/api/admin/tourist-guides/{$guide->id}")
            ->assertOk()
            ->assertJsonPath('data.tourist_guide.price_per_hour', 20)
            ->assertJsonPath('data.tourist_guide.currency', 'USD')
            ->assertJsonPath('data.tourist_guide.active_currency', 'SYP')
            ->assertJsonPath('data.tourist_guide.active_price_per_hour', 2600);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function regularUser(): User
    {
        return User::factory()->create();
    }

    private function authHeaders(User $user): array
    {
        $device = Device::create([
            'identifier' => Str::random(60),
            'refresh_token' => Str::random(64),
            'salt' => Str::random(32),
            'token_expires_at' => now()->addDays(30),
        ]);

        $token = $user->createToken('test_token');
        $token->accessToken->forceFill(['device_id' => $device->id])->save();

        $this->app['auth']->guard('sanctum')->forgetUser();

        return [
            'Authorization' => 'Bearer '.$token->plainTextToken,
            'Our-Great-Password' => 'test-secret',
            'lang' => 'en',
            'device' => $device->identifier,
            'Accept' => 'application/json',
        ];
    }
}
