<?php

namespace Tests\Feature;

use App\Jobs\ProcessTripPolylineJob;
use App\Models\City;
use App\Models\Device;
use App\Models\Hotel;
use App\Models\Region;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Notifications\TripInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TripTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.api_password' => Hash::make('test-secret')]);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    }

    public function test_owner_can_create_trip_with_generated_uuid_and_start_date(): void
    {
        $owner = $this->regularUser();

        $this->withHeaders($this->authHeaders($owner))
            ->postJson('/api/trips', ['title' => 'Damascus to Aleppo', 'start_date' => '2026-09-15'])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Trip created successfully')
            ->assertJsonPath('data', []);

        $this->assertDatabaseCount('trips', 1);
        $this->assertDatabaseHas('trips', [
            'title' => 'Damascus to Aleppo',
            'owner_id' => $owner->id,
            'status' => 'preparing',
        ]);
        $this->assertSame('2026-09-15', Trip::firstOrFail()->start_date->toDateString());
        $this->assertNotNull(Trip::firstOrFail()->uuid);
    }

    public function test_owner_can_add_planned_cities(): void
    {
        $owner = $this->regularUser();
        $city = City::factory()->create(['name_en' => 'Damascus']);
        $otherCity = City::factory()->create(['name_en' => 'Aleppo']);
        $trip = Trip::factory()->for($owner, 'owner')->create();

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/cities", [
                'cities' => [
                    ['city_id' => $city->id],
                    ['city_id' => $otherCity->id],
                ],
            ])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Cities added to trip successfully')
            ->assertJsonPath('data', []);

        $this->assertDatabaseHas('trip_cities', [
            'trip_id' => $trip->id,
            'city_id' => $city->id,
            'order' => 1,
        ]);
        $this->assertDatabaseHas('trip_cities', [
            'trip_id' => $trip->id,
            'city_id' => $otherCity->id,
            'order' => 2,
        ]);
    }

    public function test_owner_can_add_hotel_destination_linked_to_trip_city(): void
    {
        $owner = $this->regularUser();
        $city = City::factory()->create(['name_en' => 'Damascus']);
        $hotel = Hotel::factory()->for($city)->create(['name_en' => 'Beit Al Mamlouka']);
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $tripCity = $trip->cities()->create(['city_id' => $city->id, 'order' => 1]);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/destinations", [
                'trip_city_id' => $tripCity->id,
                'destinations' => [
                    ['type' => 'hotel', 'id' => $hotel->id],
                ],
            ])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Destination added successfully')
            ->assertJsonPath('data', []);

        $this->assertDatabaseHas('trip_destinations', [
            'trip_city_id' => $tripCity->id,
            'destinable_type' => 'hotel',
            'destinable_id' => $hotel->id,
            'order' => 1,
        ]);
    }

    public function test_owner_can_add_region_destination_linked_to_trip_city(): void
    {
        $owner = $this->regularUser();
        $city = City::factory()->create(['name_en' => 'Damascus']);
        $region = Region::factory()->for($city)->create(['name_en' => 'Old City']);
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $tripCity = $trip->cities()->create(['city_id' => $city->id, 'order' => 1]);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/destinations", [
                'trip_city_id' => $tripCity->id,
                'destinations' => [
                    ['type' => 'region', 'id' => $region->id],
                ],
            ])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Destination added successfully')
            ->assertJsonPath('data', []);

        $this->assertDatabaseHas('trip_destinations', [
            'trip_city_id' => $tripCity->id,
            'destinable_type' => 'region',
            'destinable_id' => $region->id,
        ]);
    }

    public function test_destination_requires_valid_type_and_reference(): void
    {
        $owner = $this->regularUser();
        $city = City::factory()->create();
        $hotel = Hotel::factory()->for($city)->create();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $tripCity = $trip->cities()->create(['city_id' => $city->id, 'order' => 1]);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/destinations", [
                'trip_city_id' => $tripCity->id,
                'destinations' => [],
            ])
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/destinations", [
                'trip_city_id' => $tripCity->id,
                'destinations' => [
                    ['type' => 'restaurant', 'id' => $hotel->id],
                ],
            ])
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/destinations", [
                'trip_city_id' => $tripCity->id,
                'destinations' => [
                    ['type' => 'hotel', 'id' => 999999],
                ],
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('trip_destinations', 0);
    }

    public function test_destination_must_belong_to_the_trip_city_city(): void
    {
        $owner = $this->regularUser();
        $otherCity = City::factory()->create();
        $hotel = Hotel::factory()->for(City::factory()->create())->create();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $tripCity = $trip->cities()->create(['city_id' => $otherCity->id, 'order' => 1]);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/destinations", [
                'trip_city_id' => $tripCity->id,
                'destinations' => [
                    ['type' => 'hotel', 'id' => $hotel->id],
                ],
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('trip_destinations', 0);
    }

    public function test_cannot_add_the_same_destination_twice_in_one_request(): void
    {
        $owner = $this->regularUser();
        $city = City::factory()->create();
        $hotel = Hotel::factory()->for($city)->create();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $tripCity = $trip->cities()->create(['city_id' => $city->id, 'order' => 1]);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/destinations", [
                'trip_city_id' => $tripCity->id,
                'destinations' => [
                    ['type' => 'hotel', 'id' => $hotel->id],
                    ['type' => 'hotel', 'id' => $hotel->id],
                ],
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('trip_destinations', 0);
    }

    public function test_cannot_add_the_same_destination_twice_to_a_city(): void
    {
        $owner = $this->regularUser();
        $city = City::factory()->create();
        $hotel = Hotel::factory()->for($city)->create();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $tripCity = $trip->cities()->create(['city_id' => $city->id, 'order' => 1]);
        $tripCity->destinations()->create([
            'destinable_type' => $hotel->getMorphClass(),
            'destinable_id' => $hotel->id,
            'order' => 1,
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/destinations", [
                'trip_city_id' => $tripCity->id,
                'destinations' => [
                    ['type' => 'hotel', 'id' => $hotel->id],
                ],
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('trip_destinations', 1);
    }

    public function test_owner_can_remove_city_and_destination(): void
    {
        $owner = $this->regularUser();
        $city = City::factory()->create();
        $region = Region::factory()->for($city)->create();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $tripCity = $trip->cities()->create(['city_id' => $city->id, 'order' => 1]);
        $destination = $tripCity->destinations()->create([
            'destinable_type' => $region->getMorphClass(),
            'destinable_id' => $region->id,
            'order' => 1,
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->deleteJson("/api/trips/{$trip->id}/destinations/{$destination->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Destination removed from trip successfully');

        $this->withHeaders($this->authHeaders($owner))
            ->deleteJson("/api/trips/{$trip->id}/cities/{$tripCity->id}")
            ->assertOk()
            ->assertJsonPath('message', 'City removed from trip successfully');

        $this->assertDatabaseCount('trip_cities', 0);
        $this->assertDatabaseCount('trip_destinations', 0);
    }

    public function test_owner_can_update_planned_cities_replacing_the_itinerary(): void
    {
        $owner = $this->regularUser();
        $damascus = City::factory()->create(['name_en' => 'Damascus']);
        $aleppo = City::factory()->create(['name_en' => 'Aleppo']);
        $homs = City::factory()->create(['name_en' => 'Homs']);
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $trip->cities()->create(['city_id' => $damascus->id, 'order' => 1]);
        $trip->cities()->create(['city_id' => $aleppo->id, 'order' => 2]);

        $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/trips/{$trip->id}/cities", [
                'cities' => [
                    ['city_id' => $homs->id, 'order' => 1],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Cities updated successfully')
            ->assertJsonPath('data', []);

        $this->assertDatabaseCount('trip_cities', 1);
        $this->assertDatabaseHas('trip_cities', [
            'trip_id' => $trip->id,
            'city_id' => $homs->id,
            'order' => 1,
        ]);
    }

    public function test_updating_cities_removes_destinations_of_dropped_cities(): void
    {
        $owner = $this->regularUser();
        $damascus = City::factory()->create();
        $homs = City::factory()->create();
        $hotel = Hotel::factory()->for($damascus)->create();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $tripCity = $trip->cities()->create(['city_id' => $damascus->id, 'order' => 1]);
        $tripCity->destinations()->create([
            'destinable_type' => $hotel->getMorphClass(),
            'destinable_id' => $hotel->id,
            'order' => 1,
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/trips/{$trip->id}/cities", [
                'cities' => [
                    ['city_id' => $homs->id],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseCount('trip_cities', 1);
        $this->assertDatabaseCount('trip_destinations', 0);
    }

    public function test_owner_can_update_planned_destinations_replacing_the_list(): void
    {
        $owner = $this->regularUser();
        $city = City::factory()->create();
        $hotel = Hotel::factory()->for($city)->create(['name_en' => 'Beit Al Mamlouka']);
        $region = Region::factory()->for($city)->create(['name_en' => 'Old City']);
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $tripCity = $trip->cities()->create(['city_id' => $city->id, 'order' => 1]);
        $tripCity->destinations()->create([
            'destinable_type' => $hotel->getMorphClass(),
            'destinable_id' => $hotel->id,
            'order' => 1,
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/trips/{$trip->id}/destinations", [
                'trip_city_id' => $tripCity->id,
                'destinations' => [
                    ['type' => 'region', 'id' => $region->id],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Destinations updated successfully')
            ->assertJsonPath('data', []);

        $this->assertDatabaseCount('trip_destinations', 1);
        $this->assertDatabaseHas('trip_destinations', [
            'trip_city_id' => $tripCity->id,
            'destinable_type' => 'region',
            'destinable_id' => $region->id,
            'order' => 1,
        ]);
    }

    public function test_non_owner_cannot_add_cities(): void
    {
        $stranger = $this->regularUser();
        $city = City::factory()->create();
        $trip = Trip::factory()->for($this->regularUser(), 'owner')->create();

        $this->withHeaders($this->authHeaders($stranger))
            ->postJson("/api/trips/{$trip->id}/cities", [
                'cities' => [
                    ['city_id' => $city->id],
                ],
            ])
            ->assertStatus(403);

        $this->assertDatabaseCount('trip_cities', 0);
    }

    public function test_owner_and_editor_can_push_location_pings(): void
    {
        $owner = $this->regularUser();
        $editor = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $trip->memberPivots()->create(['user_id' => $editor->id, 'role' => 'editor', 'status' => 'approved']);

        $this->prepareAndStartTrip($trip, $owner);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/pings", ['latitude' => 33.51, 'longitude' => 36.29])
            ->assertOk()
            ->assertJsonPath('message', 'Location ping recorded successfully');

        $this->withHeaders($this->authHeaders($editor))
            ->postJson("/api/trips/{$trip->id}/pings", ['latitude' => 33.52, 'longitude' => 36.30])
            ->assertOk();

        $this->assertDatabaseCount('trip_locations', 2);
    }

    public function test_the_same_location_cannot_be_pinged_twice(): void
    {
        $owner = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();

        $this->prepareAndStartTrip($trip, $owner);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/pings", ['latitude' => 33.51, 'longitude' => 36.29])
            ->assertOk();

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/pings", ['latitude' => 33.51, 'longitude' => 36.29])
            ->assertStatus(422);

        $this->assertDatabaseCount('trip_locations', 1);
    }

    public function test_viewer_member_and_stranger_cannot_push_pings(): void
    {
        $owner = $this->regularUser();
        $viewer = $this->regularUser();
        $stranger = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $trip->memberPivots()->create(['user_id' => $viewer->id, 'role' => 'viewer', 'status' => 'approved']);

        $payload = ['latitude' => 33.51, 'longitude' => 36.29];

        $this->withHeaders($this->authHeaders($viewer))
            ->postJson("/api/trips/{$trip->id}/pings", $payload)
            ->assertStatus(403);

        $this->withHeaders($this->authHeaders($stranger))
            ->postJson("/api/trips/{$trip->id}/pings", $payload)
            ->assertStatus(403);

        $this->assertDatabaseCount('trip_locations', 0);
    }

    public function test_editor_can_create_trip_note_with_description(): void
    {
        $owner = $this->regularUser();
        $editor = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $trip->memberPivots()->create(['user_id' => $editor->id, 'role' => 'editor', 'status' => 'approved']);

        $this->prepareAndStartTrip($trip, $owner);

        $this->withHeaders($this->authHeaders($editor))
            ->postJson("/api/trips/{$trip->id}/notes", [
                'latitude' => 33.515,
                'longitude' => 36.295,
                'description' => 'Had lunch at the old bazaar.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Trip note created successfully')
            ->assertJsonPath('data', []);

        $this->assertDatabaseCount('trip_notes', 1);
        $this->assertDatabaseHas('trip_notes', [
            'description' => 'Had lunch at the old bazaar.',
        ]);
        $this->assertDatabaseCount('descriptions', 0);
    }

    public function test_trip_note_accepts_picture_attachment(): void
    {
        Storage::fake('public');

        $owner = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();

        $this->prepareAndStartTrip($trip, $owner);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/notes", [
                'latitude' => 33.515,
                'longitude' => 36.295,
                'description' => 'A snapshot of the citadel.',
                'pictures' => [UploadedFile::fake()->image('snapshot.jpg', 100, 100)],
            ])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Trip note created successfully');

        $this->assertDatabaseCount('media', 1);
        $this->assertDatabaseHas('media', [
            'model_type' => 'trip_note',
            'collection_name' => 'trip_note_pictures',
        ]);
    }

    public function test_owner_can_invite_and_remove_members_by_username(): void
    {
        Notification::fake();

        $owner = $this->regularUser();
        $collaborator = User::factory()->create();
        $trip = Trip::factory()->for($owner, 'owner')->create();

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/members", ['user_name' => $collaborator->username])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Invitation sent successfully')
            ->assertJsonPath('data', []);

        $this->assertDatabaseHas('trip_members', [
            'trip_id' => $trip->id,
            'user_id' => $collaborator->id,
            'role' => 'viewer',
            'status' => 'pending',
        ]);

        Notification::assertSentTo($collaborator, TripInvitationNotification::class);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/members", ['user_name' => 'ghost_user'])
            ->assertStatus(422);

        $member = TripMember::where('trip_id', $trip->id)->where('user_id', $collaborator->id)->firstOrFail();

        $this->withHeaders($this->authHeaders($owner))
            ->deleteJson("/api/trips/{$trip->id}/members/{$member->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Member removed successfully');

        $this->assertDatabaseCount('trip_members', 0);
    }

    public function test_owner_cannot_be_invited_to_their_own_trip(): void
    {
        $owner = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/members", ['user_name' => $owner->username])
            ->assertStatus(422);
    }

    public function test_user_can_only_be_invited_once_to_a_trip(): void
    {
        Notification::fake();

        $owner = $this->regularUser();
        $collaborator = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/members", ['user_name' => $collaborator->username])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Invitation sent successfully');

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/members", ['user_name' => $collaborator->username])
            ->assertStatus(422);

        $this->assertDatabaseCount('trip_members', 1);
        Notification::assertSentToTimes($collaborator, TripInvitationNotification::class, 1);
    }

    public function test_owner_cannot_be_removed_and_editors_cannot_manage_members(): void
    {
        $owner = $this->regularUser();
        $editor = $this->regularUser();
        $guest = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $trip->memberPivots()->create(['user_id' => $editor->id, 'role' => 'editor', 'status' => 'approved']);
        $ownerPivot = $trip->memberPivots()->create(['user_id' => $owner->id, 'role' => 'viewer', 'status' => 'approved']);

        $this->withHeaders($this->authHeaders($owner))
            ->deleteJson("/api/trips/{$trip->id}/members/{$ownerPivot->id}")
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($editor))
            ->postJson("/api/trips/{$trip->id}/members", ['user_name' => $guest->username])
            ->assertStatus(403);
    }

    public function test_shared_trip_is_public_and_reports_correct_roles(): void
    {
        $owner = $this->regularUser();
        $editor = $this->regularUser();
        $city = City::factory()->create(['name_en' => 'Homs']);
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $trip->memberPivots()->create(['user_id' => $editor->id, 'role' => 'editor', 'status' => 'approved']);
        $trip->cities()->create(['city_id' => $city->id, 'order' => 1]);

        $guestHeaders = [
            'Our-Great-Password' => 'test-secret',
            'lang' => 'en',
            'Accept' => 'application/json',
        ];

        $this->withHeaders($guestHeaders)
            ->getJson("/api/shared-trips/{$trip->uuid}")
            ->assertOk()
            ->assertJsonPath('data.role', 'viewer')
            ->assertJsonPath('data.cities.0.city.name', 'Homs');

        $this->withHeaders($this->authHeaders($owner))
            ->getJson("/api/shared-trips/{$trip->uuid}")
            ->assertOk()
            ->assertJsonPath('data.role', 'owner');

        $this->withHeaders($this->authHeaders($editor))
            ->getJson("/api/shared-trips/{$trip->uuid}")
            ->assertOk()
            ->assertJsonPath('data.role', 'editor');
    }

    public function test_invited_user_can_accept_invitation_and_only_the_invitee_can_respond(): void
    {
        $owner = $this->regularUser();
        $invitee = $this->regularUser();
        $stranger = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $trip->memberPivots()->create(['user_id' => $invitee->id, 'role' => 'viewer', 'status' => 'pending']);

        $this->withHeaders($this->authHeaders($stranger))
            ->postJson("/api/trips/{$trip->id}/members/{$invitee->id}/accept")
            ->assertStatus(403);

        $this->withHeaders($this->authHeaders($invitee))
            ->postJson("/api/trips/{$trip->id}/members/{$invitee->id}/accept")
            ->assertOk()
            ->assertJsonPath('message', 'Invitation accepted successfully');

        $this->assertDatabaseHas('trip_members', [
            'trip_id' => $trip->id,
            'user_id' => $invitee->id,
            'role' => 'viewer',
            'status' => 'approved',
        ]);

        $this->assertNotNull(
            TripMember::where('trip_id', $trip->id)->where('user_id', $invitee->id)->value('responded_at')
        );

        $this->withHeaders($this->authHeaders($invitee))
            ->getJson("/api/trips/{$trip->id}")
            ->assertOk()
            ->assertJsonPath('data.role', 'viewer');
    }

    public function test_invitee_can_reject_invitation_and_stays_blocked(): void
    {
        $owner = $this->regularUser();
        $invitee = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $trip->memberPivots()->create(['user_id' => $invitee->id, 'role' => 'viewer', 'status' => 'pending']);

        $this->withHeaders($this->authHeaders($invitee))
            ->postJson("/api/trips/{$trip->id}/members/{$invitee->id}/reject")
            ->assertOk()
            ->assertJsonPath('message', 'Invitation rejected successfully');

        $this->assertDatabaseHas('trip_members', [
            'trip_id' => $trip->id,
            'user_id' => $invitee->id,
            'status' => 'rejected',
        ]);

        $this->withHeaders($this->authHeaders($invitee))
            ->getJson("/api/trips/{$trip->id}")
            ->assertStatus(403);

        $this->withHeaders($this->authHeaders($invitee))
            ->postJson("/api/trips/{$trip->id}/members/{$invitee->id}/reject")
            ->assertStatus(422);
    }

    public function test_pending_invitee_cannot_access_the_trip_until_accepted(): void
    {
        $owner = $this->regularUser();
        $invitee = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $trip->memberPivots()->create(['user_id' => $invitee->id, 'role' => 'viewer', 'status' => 'pending']);

        $this->withHeaders($this->authHeaders($invitee))
            ->getJson("/api/trips/{$trip->id}")
            ->assertStatus(403);

        $this->withHeaders($this->authHeaders($invitee))
            ->getJson('/api/trips')
            ->assertOk()
            ->assertJsonCount(0, 'data.trips');
    }

    public function test_owner_can_update_member_role_between_viewer_and_editor(): void
    {
        $owner = $this->regularUser();
        $member = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $trip->memberPivots()->create(['user_id' => $member->id, 'role' => 'viewer', 'status' => 'approved']);

        $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/trips/{$trip->id}/members/{$member->id}", ['role' => 'editor'])
            ->assertOk()
            ->assertJsonPath('message', 'Member role updated successfully');

        $this->assertDatabaseHas('trip_members', [
            'trip_id' => $trip->id,
            'user_id' => $member->id,
            'role' => 'editor',
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/trips/{$trip->id}/members/{$member->id}", ['role' => 'superuser'])
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/trips/{$trip->id}/members/{$member->id}", ['role' => 'viewer'])
            ->assertOk();

        $this->assertDatabaseHas('trip_members', [
            'trip_id' => $trip->id,
            'user_id' => $member->id,
            'role' => 'viewer',
        ]);

        $this->withHeaders($this->authHeaders($member))
            ->putJson("/api/trips/{$trip->id}/members/{$owner->id}", ['role' => 'viewer'])
            ->assertStatus(403);
    }

    public function test_owner_can_regenerate_the_trip_link_uuid(): void
    {
        $owner = $this->regularUser();
        $editor = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $trip->memberPivots()->create(['user_id' => $editor->id, 'role' => 'editor', 'status' => 'approved']);
        $oldUuid = $trip->uuid;

        $this->withHeaders($this->authHeaders($editor))
            ->postJson("/api/trips/{$trip->id}/rotate-link")
            ->assertStatus(403);

        $response = $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/rotate-link")
            ->assertOk()
            ->assertJsonPath('message', 'Trip link regenerated successfully');

        $newUuid = $response->json('data.uuid');

        $this->assertNotEquals($oldUuid, $newUuid);
        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'uuid' => $newUuid]);

        $guestHeaders = [
            'Authorization' => '',
            'Our-Great-Password' => 'test-secret',
            'lang' => 'en',
            'Accept' => 'application/json',
        ];

        $this->app['auth']->guard('sanctum')->forgetUser();

        $this->withHeaders($guestHeaders)
            ->getJson("/api/shared-trips/{$oldUuid}")
            ->assertStatus(404);

        $this->withHeaders($guestHeaders)
            ->getJson("/api/shared-trips/{$newUuid}")
            ->assertOk()
            ->assertJsonPath('data.role', 'viewer');
    }

    public function test_end_trip_archives_polyline_prunes_telemetry_and_keeps_memories(): void
    {
        $owner = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();

        $this->prepareAndStartTrip($trip, $owner);

        foreach ([[33.51, 36.29], [33.52, 36.30], [33.53, 36.31]] as [$lat, $lng]) {
            $this->withHeaders($this->authHeaders($owner))
                ->postJson("/api/trips/{$trip->id}/pings", ['latitude' => $lat, 'longitude' => $lng])
                ->assertOk();
        }

        $note = $trip->notes()->create([
            'latitude' => 33.525,
            'longitude' => 36.305,
            'description' => 'Archived memory.',
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'finished'])
            ->assertOk()
            ->assertJsonPath('message', 'Trip status updated successfully');

        $finished = $trip->fresh();
        $this->assertDatabaseCount('trip_locations', 0);
        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => 'finished',
        ]);
        $this->assertDatabaseCount('trip_notes', 1);
        $this->assertDatabaseHas('trip_notes', ['description' => 'Archived memory.']);

        $this->assertNotNull($finished->route_polyline);
        $this->assertNotSame('', $finished->route_polyline);
    }

    public function test_route_polyline_is_omitted_until_finished_and_note_shown_while_processing(): void
    {
        Queue::fake();

        $owner = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();

        $this->withHeaders($this->authHeaders($owner))
            ->getJson("/api/trips/{$trip->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'preparing')
            ->assertJsonPath('data.route_polyline', null)
            ->assertJsonPath('data.message', null);

        $this->prepareAndStartTrip($trip, $owner);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/pings", ['latitude' => 33.51, 'longitude' => 36.29])
            ->assertOk();

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'finished'])
            ->assertOk()
            ->assertJsonPath('message', 'Trip status updated successfully');

        $this->withHeaders($this->authHeaders($owner))
            ->getJson("/api/trips/{$trip->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'finished')
            ->assertJsonPath('data.route_polyline', null)
            ->assertJsonPath('data.message', 'Your route track is being saved. You will be able to view it once it is ready.');

        Queue::assertPushed(ProcessTripPolylineJob::class);
        $this->assertDatabaseCount('trip_locations', 1);
    }

    public function test_pings_and_notes_fail_on_finished_trip(): void
    {
        $owner = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create(['status' => 'finished']);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/pings", ['latitude' => 33.51, 'longitude' => 36.29])
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/notes", [
                'latitude' => 33.51,
                'longitude' => 36.29,
                'description' => 'Too late.',
            ])
            ->assertStatus(422);
    }

    public function test_only_owner_or_admin_can_delete_trip(): void
    {
        $owner = $this->regularUser();
        $editor = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $trip->memberPivots()->create(['user_id' => $editor->id, 'role' => 'editor', 'status' => 'approved']);

        $this->withHeaders($this->authHeaders($editor))
            ->deleteJson("/api/trips/{$trip->id}")
            ->assertStatus(403);

        $this->withHeaders($this->authHeaders($owner))
            ->deleteJson("/api/trips/{$trip->id}")
            ->assertOk();

        $this->assertDatabaseCount('trips', 0);
    }

    public function test_status_transitions_lock_down_the_itinerary(): void
    {
        $owner = $this->regularUser();
        $city = City::factory()->create();
        $hotel = Hotel::factory()->for($city)->create();
        $trip = Trip::factory()->for($owner, 'owner')->create();

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/cities", [
                'cities' => [
                    ['city_id' => $city->id],
                ],
            ])
            ->assertStatus(201);

        $tripCityId = $trip->cities()->firstOrFail()->id;

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'prepared'])
            ->assertOk()
            ->assertJsonPath('message', 'Trip status updated successfully');

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/cities", [
                'cities' => [
                    ['city_id' => $city->id],
                ],
            ])
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/trips/{$trip->id}/cities", [
                'cities' => [
                    ['city_id' => $city->id],
                ],
            ])
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/destinations", [
                'trip_city_id' => $tripCityId,
                'destinations' => [
                    ['type' => 'hotel', 'id' => $hotel->id],
                ],
            ])
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/trips/{$trip->id}/destinations", [
                'trip_city_id' => $tripCityId,
                'destinations' => [
                    ['type' => 'hotel', 'id' => $hotel->id],
                ],
            ])
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}", ['title' => 'Too late'])
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'active'])
            ->assertOk();

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/destinations", [
                'trip_city_id' => $tripCityId,
                'destinations' => [
                    ['type' => 'hotel', 'id' => $hotel->id],
                ],
            ])
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'finished'])
            ->assertOk();

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/pings", ['latitude' => 33.51, 'longitude' => 36.29])
            ->assertStatus(422);
    }

    public function test_prepare_and_start_require_the_owner(): void
    {
        $owner = $this->regularUser();
        $stranger = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();

        $this->withHeaders($this->authHeaders($stranger))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'prepared'])
            ->assertStatus(403);

        $this->withHeaders($this->authHeaders($stranger))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'active'])
            ->assertStatus(403);

        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'status' => 'preparing']);
    }

    public function test_status_transitions_must_follow_the_lifecycle(): void
    {
        $owner = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create(['status' => 'preparing']);

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'active'])
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'finished'])
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'preparing'])
            ->assertStatus(422);

        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'status' => 'preparing']);

        $this->prepareAndStartTrip($trip, $owner);

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'prepared'])
            ->assertStatus(422);

        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'status' => 'active']);
    }

    public function test_editor_can_finish_the_trip_but_not_prepare_or_start_it(): void
    {
        Queue::fake();

        $owner = $this->regularUser();
        $editor = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $trip->memberPivots()->create(['user_id' => $editor->id, 'role' => 'editor', 'status' => 'approved']);

        $this->withHeaders($this->authHeaders($editor))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'prepared'])
            ->assertStatus(403);

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'prepared'])
            ->assertOk();

        $this->withHeaders($this->authHeaders($editor))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'active'])
            ->assertStatus(403);

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'active'])
            ->assertOk();

        $this->withHeaders($this->authHeaders($editor))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'finished'])
            ->assertOk()
            ->assertJsonPath('message', 'Trip status updated successfully');

        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'status' => 'finished']);
    }

    public function test_owner_can_update_trip_details_only_while_preparing(): void
    {
        $owner = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create(['title' => 'Original']);

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}", [
                'title' => 'Updated Title',
                'start_date' => '2026-10-01',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Trip updated successfully')
            ->assertJsonPath('data', []);

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'title' => 'Updated Title',
        ]);
        $this->assertSame('2026-10-01', $trip->fresh()->start_date->toDateString());

        $this->prepareAndStartTrip($trip, $owner);

        $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/trips/{$trip->id}", ['title' => 'Nope'])
            ->assertStatus(422);
    }

    public function test_status_label_is_localized(): void
    {
        $owner = $this->regularUser();
        $trip = Trip::factory()->for($owner, 'owner')->create();

        $this->withHeaders($this->authHeaders($owner))
            ->getJson("/api/trips/{$trip->id}")
            ->assertOk()
            ->assertJsonPath('data.status_label', 'Preparing');

        $arabicHeaders = array_replace($this->authHeaders($owner), ['lang' => 'ar']);

        $this->withHeaders($arabicHeaders)
            ->getJson("/api/trips/{$trip->id}")
            ->assertOk()
            ->assertJsonPath('data.status_label', 'قيد التحضير');
    }

    public function test_trip_auto_finishes_when_all_destinations_are_visited(): void
    {
        $owner = $this->regularUser();
        $city = City::factory()->create();
        $region = Region::factory()->for($city)->create(['name_en' => 'Old City']);
        $region->location()->create(['latitude' => 33.51, 'longitude' => 36.29]);
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $tripCity = $trip->cities()->create(['city_id' => $city->id, 'order' => 1]);
        $destination = $tripCity->destinations()->create([
            'destinable_type' => $region->getMorphClass(),
            'destinable_id' => $region->id,
            'order' => 1,
        ]);

        $this->prepareAndStartTrip($trip, $owner);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/pings", ['latitude' => 33.51, 'longitude' => 36.29])
            ->assertOk()
            ->assertJsonPath('message', 'Location ping recorded successfully');

        $finished = $trip->fresh();
        $this->assertSame('finished', $finished->status->value);
        $this->assertNotNull($finished->route_polyline);
        $this->assertNotNull($destination->fresh()->visited_at);
        $this->assertDatabaseCount('trip_locations', 0);
    }

    public function test_index_returns_only_trip_basics(): void
    {
        $owner = $this->regularUser();
        Trip::factory()->for($owner, 'owner')->create([
            'title' => 'Damascus to Aleppo',
            'start_date' => '2026-09-15',
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->getJson('/api/trips')
            ->assertOk()
            ->assertJsonPath('data.trips.0.title', 'Damascus to Aleppo')
            ->assertJsonPath('data.trips.0.start_date', '2026-09-15')
            ->assertJsonPath('data.trips.0.status', 'preparing')
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonMissingPath('data.trips.0.cities')
            ->assertJsonMissingPath('data.trips.0.notes')
            ->assertJsonMissingPath('data.trips.0.route_polyline')
            ->assertJsonMissingPath('data.trips.0.owner')
            ->assertJsonMissingPath('data.trips.0.role');
    }

    public function test_show_returns_destinations_with_locations_and_visited_status(): void
    {
        $owner = $this->regularUser();
        $city = City::factory()->create(['name_en' => 'Damascus']);
        $region = Region::factory()->for($city)->create(['name_en' => 'Old City']);
        $region->location()->create(['latitude' => 33.5127, 'longitude' => 36.2915]);
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $tripCity = $trip->cities()->create(['city_id' => $city->id, 'order' => 1]);
        $tripCity->destinations()->create([
            'destinable_type' => $region->getMorphClass(),
            'destinable_id' => $region->id,
            'order' => 1,
            'visited_at' => now(),
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->getJson("/api/trips/{$trip->id}")
            ->assertOk()
            ->assertJsonPath('data.cities.0.destinations.0.visited', true)
            ->assertJsonPath('data.cities.0.destinations.0.destination.name', 'Old City')
            ->assertJsonPath('data.cities.0.destinations.0.destination.latitude', 33.5127)
            ->assertJsonPath('data.cities.0.destinations.0.destination.longitude', 36.2915);
    }

    public function test_ping_marks_nearby_destination_visited_without_finishing(): void
    {
        $owner = $this->regularUser();
        $city = City::factory()->create(['name_en' => 'Damascus']);
        $first = Region::factory()->for($city)->create(['name_en' => 'Old City']);
        $first->location()->create(['latitude' => 33.5127, 'longitude' => 36.2915]);
        $second = Region::factory()->for($city)->create(['name_en' => 'Kafr Souseh']);
        $second->location()->create(['latitude' => 33.5062, 'longitude' => 36.3128]);
        $trip = Trip::factory()->for($owner, 'owner')->create();
        $tripCity = $trip->cities()->create(['city_id' => $city->id, 'order' => 1]);
        $firstDestination = $tripCity->destinations()->create([
            'destinable_type' => $first->getMorphClass(),
            'destinable_id' => $first->id,
            'order' => 1,
        ]);
        $secondDestination = $tripCity->destinations()->create([
            'destinable_type' => $second->getMorphClass(),
            'destinable_id' => $second->id,
            'order' => 2,
        ]);

        $this->prepareAndStartTrip($trip, $owner);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/trips/{$trip->id}/pings", ['latitude' => 33.5127, 'longitude' => 36.2915])
            ->assertOk();

        $this->assertNotNull($firstDestination->fresh()->visited_at);
        $this->assertNull($secondDestination->fresh()->visited_at);
        $this->assertSame('active', $trip->fresh()->status->value);
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

    private function prepareAndStartTrip(Trip $trip, User $user): void
    {
        $this->withHeaders($this->authHeaders($user))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'prepared'])
            ->assertOk();

        $this->withHeaders($this->authHeaders($user))
            ->patchJson("/api/trips/{$trip->id}/status", ['status' => 'active'])
            ->assertOk();
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

        // The sanctum RequestGuard caches the authenticated user across requests
        // within the same test process; reset it so each request re-authenticates.
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
