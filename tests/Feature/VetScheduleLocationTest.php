<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Models\Breed;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use App\Models\ClientProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VetScheduleLocationTest extends TestCase
{
    use RefreshDatabase;

    private User $vet;
    private User $client;
    private VetProfile $vetProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vet = User::factory()->create([
            'role' => 'veterinarian',
            'status' => 'active',
            'name' => 'Dr. Gregory House, DVM',
        ]);

        $this->vetProfile = VetProfile::create([
            'user_id' => $this->vet->id,
            'license_number' => 'PRC-LOC-12345',
            'clinic_name' => 'House Animal Clinic',
            'clinic_address' => '123 Medical Center Way',
            'city' => 'Taguig',
            'province' => 'Metro Manila',
            'latitude' => 14.5547291,
            'longitude' => 121.0244452,
            'years_experience' => 10,
            'animals_handled' => ['Dog', 'Cat'],
            'consultation_fee' => 600.00,
            'expertise' => 'Diagnostics & Internal Medicine',
            'bio' => 'Experienced vet specialized in complex diagnostics.',
            'languages' => 'English, Filipino',
            'is_available' => true,
        ]);

        $this->client = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
            'name' => 'John Client',
            'credits' => 1000,
        ]);

        ClientProfile::create([
            'user_id' => $this->client->id,
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'latitude' => 14.5547,
            'longitude' => 121.0244,
        ]);
    }

    public function test_vet_can_view_schedule_page_with_map_and_privacy_notice(): void
    {
        $response = $this->actingAs($this->vet)->get(route('vet.schedule.index'));

        $response->assertOk();
        $response->assertSee('Practice Location on Map', false);
        $response->assertSee('Doctor Privacy Guarantee', false);
        $response->assertSee('Your exact map pin and GPS coordinates are', false);
        $response->assertSee('strictly confidential', false);
        $response->assertSee('never be shown to clients', false);
        $response->assertSee('vet-location-map', false);
        $response->assertSee('input-latitude', false);
        $response->assertSee('input-longitude', false);
        $response->assertSee('leaflet.js', false);
    }

    public function test_vet_can_update_profile_location_coordinates(): void
    {
        $newLat = 14.6091000;
        $newLng = 121.0223000;

        $response = $this->actingAs($this->vet)->post(route('vet.schedule.profile'), [
            'consultation_fee' => 650.00,
            'additional_pet_fee' => 250.00,
            'additional_pet_duration' => 15,
            'clinic_name' => 'House Animal Clinic & Hospital',
            'clinic_address' => '456 Bonifacio Global City',
            'city' => 'Taguig City',
            'province' => 'Metro Manila',
            'latitude' => $newLat,
            'longitude' => $newLng,
            'expertise' => 'Diagnostics & Surgery',
            'bio' => 'Updated biography with surgery focus.',
            'languages' => 'English, Filipino, Ilocano',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vet_profiles', [
            'user_id' => $this->vet->id,
            'clinic_name' => 'House Animal Clinic & Hospital',
            'city' => 'Taguig City',
            'latitude' => $newLat,
            'longitude' => $newLng,
        ]);
    }

    public function test_vet_profile_coordinates_are_hidden_from_json_and_array_serialization(): void
    {
        $profile = VetProfile::find($this->vetProfile->id);

        $array = $profile->toArray();
        $json = $profile->toJson();

        // Ensure latitude and longitude are hidden from public serialization
        $this->assertArrayNotHasKey('latitude', $array);
        $this->assertArrayNotHasKey('longitude', $array);
        $this->assertStringNotContainsString('"latitude":', $json);
        $this->assertStringNotContainsString('"longitude":', $json);
    }

    public function test_client_search_calculates_distance_using_coordinates_without_leaking_them(): void
    {
        $dogType = AnimalType::create([
            'name' => 'Dog',
            'icon' => 'fa-dog',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->client)->get(route('client.vets.search'));

        $response->assertOk();
        // Distance is calculated and shown to client (e.g. "km away")
        $response->assertSee('km away', false);

        // Exact coordinates are never printed on client-facing search page
        $response->assertDontSee('14.5547291', false);
        $response->assertDontSee('121.0244452', false);
    }

    public function test_vet_can_update_profile_via_ajax_without_page_refresh(): void
    {
        $newLat = 14.5378;
        $newLng = 121.0014;

        $response = $this->actingAs($this->vet)->postJson(route('vet.schedule.profile'), [
            'consultation_fee' => 700.00,
            'additional_pet_fee' => 300.00,
            'additional_pet_duration' => 20,
            'clinic_name' => 'St. Francis Animal Medical Clinic',
            'clinic_address' => '789 Pasay Road',
            'city' => 'Makati City',
            'province' => 'Metro Manila',
            'latitude' => $newLat,
            'longitude' => $newLng,
            'expertise' => 'Emergency & Critical Care',
            'bio' => '24/7 Emergency and trauma medicine.',
            'languages' => 'English, Filipino',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Profile & consultation settings updated successfully.',
            'profile' => [
                'clinic_name' => 'St. Francis Animal Medical Clinic',
                'city' => 'Makati City',
                'province' => 'Metro Manila',
                'has_coordinates' => true,
            ],
        ]);

        $this->assertDatabaseHas('vet_profiles', [
            'user_id' => $this->vet->id,
            'clinic_name' => 'St. Francis Animal Medical Clinic',
            'city' => 'Makati City',
            'latitude' => $newLat,
            'longitude' => $newLng,
        ]);
    }
}
