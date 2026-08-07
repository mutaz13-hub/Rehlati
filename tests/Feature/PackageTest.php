<?php

namespace Tests\Feature;

use App\Models\CarAgency;
use App\Models\City;
use App\Models\Device;
use App\Models\Hotel;
use App\Models\Package;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PackageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.api_password' => Hash::make('test-secret')]);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    }

    public function test_admin_can_create_package_with_associations(): void
    {
        $city = City::factory()->create();
        $region = Region::factory()->create();
        $hotel = Hotel::factory()->create();
        $carAgency = CarAgency::factory()->create();

        $response = $this->withHeaders($this->authHeaders($this->adminUser()))
            ->postJson('/api/admin/packages', [
                'name_en' => 'Damascus Discovery',
                'name_ar' => 'اكتشاف دمشق',
                'description_en' => 'A five day journey through Damascus.',
                'description_ar' => 'رحلة لمدة خمسة أيام في دمشق.',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-05',
                'duration_days' => 5,
                'price' => 1500,
                'currency' => 'SYP',
                'status' => 'active',
                'regions' => [$region->id],
                'cities' => [$city->id],
                'hotels' => [$hotel->id],
                'car_agencies' => [$carAgency->id],
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Package created successfully']);

        $package = Package::firstOrFail();

        $this->assertSame('Damascus Discovery', $package->name_en);
        $this->assertSame('active', $package->status->value);
        $this->assertSame('1500.00', $package->price);

        $this->assertDatabaseHas('descriptions', [
            'describable_type' => 'package',
            'describable_id' => $package->id,
            'description_en' => 'A five day journey through Damascus.',
        ]);
        $this->assertDatabaseHas('packageables', [
            'package_id' => $package->id,
            'packageable_id' => $region->id,
            'packageable_type' => 'region',
        ]);
        $this->assertDatabaseHas('packageables', [
            'package_id' => $package->id,
            'packageable_id' => $city->id,
            'packageable_type' => 'city',
        ]);
        $this->assertDatabaseHas('packageables', [
            'package_id' => $package->id,
            'packageable_id' => $hotel->id,
            'packageable_type' => 'hotel',
        ]);
        $this->assertDatabaseHas('packageables', [
            'package_id' => $package->id,
            'packageable_id' => $carAgency->id,
            'packageable_type' => 'car_agency',
        ]);
    }

    public function test_listing_returns_paginated_packages(): void
    {
        Package::factory()->count(12)->create();

        $response = $this->withHeaders($this->authHeaders($this->regularUser()))
            ->getJson('/api/packages');

        $response->assertOk()
            ->assertJsonPath('message', 'Packages fetched successfully')
            ->assertJsonCount(10, 'data.packages')
            ->assertJsonPath('data.meta.total', 12)
            ->assertJsonPath('data.meta.current_page', 1);
    }

    public function test_listing_filters_by_status_and_association(): void
    {
        $region = Region::factory()->create();

        Package::factory()->count(3)->create(['status' => 'active']);
        Package::factory()->create(['status' => 'draft']);

        $matching = Package::factory()->create(['status' => 'active']);
        $matching->regions()->attach($region->id);

        $response = $this->withHeaders($this->authHeaders($this->regularUser()))
            ->getJson('/api/packages?status=active&region_id='.$region->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data.packages')
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.packages.0.id', $matching->id)
            ->assertJsonPath('data.packages.0.regions', [$region->id]);
    }

    public function test_listing_search_query_filters_by_name(): void
    {
        Package::factory()->create(['name_en' => 'Damascus Old City Walk']);
        Package::factory()->create(['name_en' => 'Aleppo Citadel Tour']);

        $response = $this->withHeaders($this->authHeaders($this->regularUser()))
            ->getJson('/api/packages?q=damascus');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.packages.0.name', 'Damascus Old City Walk');
    }

    public function test_show_returns_package_with_association_ids(): void
    {
        $city = City::factory()->create();
        $region = Region::factory()->create();
        $hotel = Hotel::factory()->create();
        $carAgency = CarAgency::factory()->create();

        $package = Package::factory()->create([
            'name_en' => 'Damascus Discovery',
            'status' => 'active',
        ]);
        $package->description()->create(['description_en' => 'A journey.', 'description_ar' => 'رحلة.']);
        $package->regions()->attach($region->id);
        $package->cities()->attach($city->id);
        $package->hotels()->attach($hotel->id);
        $package->carAgencies()->attach($carAgency->id);

        $response = $this->withHeaders($this->authHeaders($this->regularUser()))
            ->getJson("/api/packages/{$package->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $package->id)
            ->assertJsonPath('data.name', 'Damascus Discovery')
            ->assertJsonPath('data.start_date', $package->start_date->toDateString())
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.regions', [$region->id])
            ->assertJsonPath('data.cities', [$city->id])
            ->assertJsonPath('data.hotels', [$hotel->id])
            ->assertJsonPath('data.car_agencies', [$carAgency->id]);
    }

    public function test_show_returns_404_for_non_existent_package(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->regularUser()))
            ->getJson('/api/packages/99999');

        $response->assertStatus(404);
    }

    public function test_validation_fails_for_invalid_date_range(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->adminUser()))
            ->postJson('/api/admin/packages', [
                'name_en' => 'Broken Trip',
                'name_ar' => 'رحلة خاطئة',
                'start_date' => '2026-09-10',
                'end_date' => '2026-09-01',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseCount('packages', 0);
    }

    public function test_validation_fails_for_invalid_date_format(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->adminUser()))
            ->postJson('/api/admin/packages', [
                'name_en' => 'Broken Trip',
                'name_ar' => 'رحلة خاطئة',
                'start_date' => '10/09/2026',
                'end_date' => '2026-09-01',
            ]);

        $response->assertStatus(422);
    }

    public function test_validation_fails_for_non_existent_association_ids(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->adminUser()))
            ->postJson('/api/admin/packages', [
                'name_en' => 'Ghost Trip',
                'name_ar' => 'رحلة وهمية',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-05',
                'hotels' => [99999],
                'regions' => [99999],
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('packages', 0);
    }

    public function test_update_with_empty_association_arrays_clears_them(): void
    {
        $admin = $this->adminUser();
        $region = Region::factory()->create();
        $hotel = Hotel::factory()->create();

        $package = Package::factory()->create();
        $package->regions()->attach($region->id);
        $package->hotels()->attach($hotel->id);

        $response = $this->withHeaders($this->authHeaders($admin))
            ->putJson("/api/admin/packages/{$package->id}", [
                'hotels' => [],
                'status' => 'active',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.hotels', []);

        $this->assertDatabaseCount('packageables', 1);
        $this->assertDatabaseHas('packageables', [
            'package_id' => $package->id,
            'packageable_id' => $region->id,
            'packageable_type' => 'region',
        ]);
        $this->assertDatabaseHas('packages', ['id' => $package->id, 'status' => 'active']);
    }

    public function test_update_without_association_keys_preserves_them(): void
    {
        $admin = $this->adminUser();
        $region = Region::factory()->create();
        $hotel = Hotel::factory()->create();

        $package = Package::factory()->create();
        $package->regions()->attach($region->id);
        $package->hotels()->attach($hotel->id);

        $this->withHeaders($this->authHeaders($admin))
            ->putJson("/api/admin/packages/{$package->id}", ['name_en' => 'Updated Trip'])
            ->assertOk();

        $this->assertDatabaseCount('packageables', 2);
        $this->assertDatabaseHas('packages', ['id' => $package->id, 'name_en' => 'Updated Trip']);
    }

    public function test_admin_can_delete_package(): void
    {
        $admin = $this->adminUser();
        $package = Package::factory()->create();
        $package->regions()->attach(Region::factory()->create()->id);

        $this->withHeaders($this->authHeaders($admin))
            ->deleteJson("/api/admin/packages/{$package->id}")
            ->assertOk()
            ->assertJson(['message' => 'Package deleted successfully']);

        $this->assertDatabaseCount('packages', 0);
        $this->assertDatabaseCount('packageables', 0);
    }

    public function test_non_admin_cannot_create_package(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->regularUser()))
            ->postJson('/api/admin/packages', [
                'name_en' => 'Not Allowed',
                'name_ar' => 'غير مسموح',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-05',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseCount('packages', 0);
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

        return [
            'Authorization' => 'Bearer '.$token->plainTextToken,
            'Our-Great-Password' => 'test-secret',
            'lang' => 'en',
            'device' => $device->identifier,
            'Accept' => 'application/json',
        ];
    }
}
