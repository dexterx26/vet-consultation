<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\AnimalType;
use App\Models\Consultation;
use App\Models\SystemSetting;
use App\Models\VetProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MultiPetBookingAndFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_booking_page_preselects_pet_id_and_displays_handled_animals()
    {
        $client = User::where('role', 'client')->first();
        $vet = User::where('role', 'veterinarian')->where('status', 'active')->first();

        // Get a pet belonging to client
        $pet = Pet::where('user_id', $client->id)->first();

        $response = $this->actingAs($client)->get("/client/bookings/create?vet_id={$vet->id}&pet_id={$pet->id}");
        $response->assertOk();
        $response->assertSee($pet->name);
        $response->assertSee('bookingCalendarComponent', false);
    }

    public function test_multi_pet_booking_calculates_fees_time_credits_and_attaches_pets()
    {
        $client = User::where('role', 'client')->first();
        $vet = User::where('role', 'veterinarian')->where('status', 'active')->first();

        // Vet handles: Dog, Cat, Rabbit
        $dogType = AnimalType::where('name', 'Dog')->first();
        $catType = AnimalType::where('name', 'Cat')->first();

        // Create 2 pets for client: Cat & Dog
        $cat = Pet::create([
            'user_id' => $client->id,
            'name' => 'Mimi The Cat',
            'animal_type_id' => $catType->id,
            'sex' => 'Female',
            'age_text' => '2 years',
        ]);

        $dog = Pet::create([
            'user_id' => $client->id,
            'name' => 'Baron The Dog',
            'animal_type_id' => $dogType->id,
            'sex' => 'Male',
            'age_text' => '3 years',
        ]);

        // Configure vet profile rates
        $vet->vetProfile->update([
            'consultation_fee' => 500.00,
            'additional_pet_fee' => 250.00,
            'additional_pet_duration' => 15,
        ]);

        // Client has 1000 credits
        $client->update(['credits' => 1000]);

        $bookingDate = Carbon::now()->addDays(2)->format('Y-m-d');
        $bookingTime = '11:00:00';

        // Book consultation with Mimi (primary) and Baron (additional)
        $response = $this->actingAs($client)->post('/client/bookings', [
            'vet_id' => $vet->id,
            'pet_id' => $cat->id,
            'additional_pet_ids' => [$dog->id],
            'type' => 'video',
            'scheduled_date' => $bookingDate,
            'scheduled_time' => $bookingTime,
            'reason' => 'Routine checkup for both cat and dog together.',
        ]);

        $response->assertSessionHas('success');

        $consultation = Consultation::where('client_id', $client->id)
            ->where('vet_id', $vet->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($consultation);
        $this->assertEquals($cat->id, $consultation->pet_id);

        // 500 base + 250 extra = 750
        $this->assertEquals(750.00, (float) $consultation->fee);
        $this->assertEquals(500.00, (float) $consultation->base_fee);
        $this->assertEquals(250.00, (float) $consultation->additional_fee);

        // Base 1 min (from test seeder) + 15 min extra = 16 mins
        $this->assertEquals(16, $consultation->duration_minutes);

        // Base 300 credits + 150 extra = 450 credits
        $this->assertEquals(450, $consultation->credits_cost);

        // Check pivot table
        $this->assertEquals(2, $consultation->pets()->count());
        $this->assertTrue($consultation->pets->contains('id', $cat->id));
        $this->assertTrue($consultation->pets->contains('id', $dog->id));

        // When vet confirms the booking, 450 credits should be deducted from client
        $acceptResponse = $this->actingAs($vet)->post("/vet/requests/{$consultation->id}/accept");
        $acceptResponse->assertSessionHas('success');

        $consultation->refresh();
        $client->refresh();

        $this->assertEquals('accepted', $consultation->status);
        $this->assertEquals(450, $consultation->credits_deducted);
        $this->assertEquals(1000 - 450, $client->credits);
    }

    public function test_cannot_book_animal_type_not_handled_by_vet()
    {
        $client = User::where('role', 'client')->first();
        $vet = User::where('role', 'veterinarian')->where('status', 'active')->first();

        // Vet handles: ['Dog', 'Cat', 'Rabbit']
        $vet->vetProfile->update(['animals_handled' => ['Dog', 'Cat']]);

        // Create a Bird pet for client
        $birdType = AnimalType::firstOrCreate(['name' => 'Bird'], ['icon' => 'fa-dove']);
        $bird = Pet::create([
            'user_id' => $client->id,
            'name' => 'Tweety',
            'animal_type_id' => $birdType->id,
            'sex' => 'Male',
        ]);

        $response = $this->actingAs($client)->post('/client/bookings', [
            'vet_id' => $vet->id,
            'pet_id' => $bird->id,
            'type' => 'chat',
            'scheduled_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
            'scheduled_time' => '14:00:00',
            'reason' => 'Checking feather issues',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('consultations', ['pet_id' => $bird->id]);
    }

    public function test_vet_can_update_additional_pet_fee_and_duration()
    {
        $vet = User::where('role', 'veterinarian')->where('status', 'active')->first();

        $response = $this->actingAs($vet)->post('/vet/schedule/profile', [
            'consultation_fee' => 650.00,
            'additional_pet_fee' => 300.00,
            'additional_pet_duration' => 20,
            'clinic_name' => 'Updated Clinic Name',
            'clinic_address' => 'Updated Address',
            'expertise' => 'Surgery, Dermatology',
            'bio' => 'Updated bio text',
            'languages' => 'English, Tagalog',
        ]);

        $response->assertSessionHas('success');

        $vet->vetProfile->refresh();
        $this->assertEquals(650.00, (float) $vet->vetProfile->consultation_fee);
        $this->assertEquals(300.00, (float) $vet->vetProfile->additional_pet_fee);
        $this->assertEquals(20, $vet->vetProfile->additional_pet_duration);
    }

    public function test_admin_can_update_global_additional_pet_settings()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->post('/admin/settings', [
            'booking_credits_cost' => 300,
            'video_call_time_limit_minutes' => 15,
            'default_additional_pet_fee' => 280.00,
            'default_additional_pet_duration' => 20,
            'additional_pet_credits_cost' => 180,
        ]);

        $response->assertSessionHas('success');

        $this->assertEquals(280.00, (float) SystemSetting::get('default_additional_pet_fee'));
        $this->assertEquals(20, (int) SystemSetting::get('default_additional_pet_duration'));
        $this->assertEquals(180, (int) SystemSetting::get('additional_pet_credits_cost'));
    }

    public function test_admin_can_update_specific_vet_fees()
    {
        $admin = User::where('role', 'admin')->first();
        $vet = User::where('role', 'veterinarian')->where('status', 'active')->first();

        $response = $this->actingAs($admin)->post("/admin/vets/{$vet->id}/fees", [
            'consultation_fee' => 800.00,
            'additional_pet_fee' => 400.00,
            'additional_pet_duration' => 25,
        ]);

        $response->assertSessionHas('success');

        $vet->vetProfile->refresh();
        $this->assertEquals(800.00, (float) $vet->vetProfile->consultation_fee);
        $this->assertEquals(400.00, (float) $vet->vetProfile->additional_pet_fee);
        $this->assertEquals(25, $vet->vetProfile->additional_pet_duration);
    }

    public function test_vets_search_page_opens_modal_instead_of_redirecting_to_profile()
    {
        $client = User::where('role', 'client')->first();
        $vet = User::where('role', 'veterinarian')->where('status', 'active')->first();

        $response = $this->actingAs($client)->get('/client/vets');
        $response->assertOk();

        // Check that openProfileModal and profile modal markup are rendered
        $response->assertSee('profileModalOpen', false);
        $response->assertSee('openProfileModal', false);
        $response->assertSee('Veterinarian Profile Modal', false);

        // Check that Profile button triggers the modal and does NOT redirect to route client.vets.show
        $response->assertSee('openProfileModal', false);
        $response->assertDontSee(route('client.vets.show', ['vet' => $vet->id]));
    }

    public function test_client_dashboard_details_button_opens_pet_modal_instead_of_redirecting_to_pet_show()
    {
        $client = User::where('role', 'client')->first();
        $pet = $client->pets()->first();

        $this->assertNotNull($pet, 'Client should have at least one registered pet for this test.');

        $response = $this->actingAs($client)->get('/client/dashboard');
        $response->assertOk();

        // Check that petModalOpen, openPetModal and Pet Details Modal are present in the response
        $response->assertSee('petModalOpen', false);
        $response->assertSee('openPetModal', false);
        $response->assertSee('Pet Details Modal', false);

        // Check that the Details action uses openPetModal and does NOT link directly to route client.pets.show
        $response->assertDontSee(route('client.pets.show', $pet));
    }
}
