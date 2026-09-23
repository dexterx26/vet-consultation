<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\AnimalType;
use App\Models\Consultation;
use App\Models\VetProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VetDashboardCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $vet;
    private User $client;
    private AnimalType $dogType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vet = User::factory()->create([
            'role' => 'veterinarian',
            'status' => 'active',
            'name' => 'Dr. Gregory House',
        ]);

        VetProfile::create([
            'user_id' => $this->vet->id,
            'clinic_name' => 'Princeton Plainsboro Vet Clinic',
            'license_number' => 'PRC-VET-12345',
            'city' => 'Metro Manila',
            'province' => 'NCR',
            'consultation_fee' => 750.00,
            'is_available' => true,
        ]);

        $this->client = User::factory()->create([
            'role' => 'client',
            'name' => 'James Wilson',
        ]);

        $this->dogType = AnimalType::create([
            'name' => 'Dog',
            'icon' => 'fa-dog',
            'is_active' => true,
        ]);
    }

    public function test_vet_dashboard_loads_with_interactive_calendar(): void
    {
        $response = $this->actingAs($this->vet)->get(route('vet.dashboard'));

        $response->assertOk();
        $response->assertSee('Consultation Calendar');
        $response->assertSee('x-data="vetCalendarComponent()"', false);
        $response->assertSee('window.vetCalendarData =', false);
        $response->assertDontSee('x-data="vetCalendarComponent([{"', false);
        $response->assertSee('Selected Date');
        $response->assertSee('Month Total');
        $response->assertSee('Today');
        $response->assertSee('Month');
        $response->assertSee('Week');
        $response->assertSee('Day');
    }

    public function test_vet_dashboard_passes_calendar_consultations_data(): void
    {
        $pet = Pet::create([
            'user_id' => $this->client->id,
            'name' => 'Hector',
            'animal_type_id' => $this->dogType->id,
            'sex' => 'Male',
        ]);

        $scheduledDate = Carbon::today()->addDays(3)->format('Y-m-d');

        $consult = Consultation::create([
            'consultation_number' => 'CN-CAL-TEST-1',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $pet->id,
            'type' => 'video',
            'status' => 'accepted',
            'fee' => 750.00,
            'scheduled_at' => $scheduledDate . ' 10:30:00',
            'duration_minutes' => 30,
            'reason' => 'Annual vaccination and checkup',
        ]);

        $response = $this->actingAs($this->vet)->get(route('vet.dashboard'));

        $response->assertOk();
        $response->assertViewHas('calendarConsultations');

        $calendarData = $response->viewData('calendarConsultations');
        $this->assertNotEmpty($calendarData);

        $matchingItem = collect($calendarData)->firstWhere('consultation_number', 'CN-CAL-TEST-1');
        $this->assertNotNull($matchingItem);
        $this->assertEquals($scheduledDate, $matchingItem['date']);
        $this->assertEquals('James Wilson', $matchingItem['client_name']);
        $this->assertEquals('Hector', $matchingItem['pet_names']);
        $this->assertEquals('video', $matchingItem['type']);
        $this->assertEquals('accepted', $matchingItem['status']);
    }

    public function test_non_vet_cannot_access_vet_dashboard(): void
    {
        $response = $this->actingAs($this->client)->get(route('vet.dashboard'));
        // Client attempting to access vet dashboard is redirected or forbidden by middleware
        $this->assertTrue($response->isRedirection() || $response->isForbidden());
    }

    public function test_declined_consultation_is_hidden_from_calendar(): void
    {
        $pet = Pet::create([
            'user_id' => $this->client->id,
            'name' => 'Hector',
            'animal_type_id' => $this->dogType->id,
            'sex' => 'Male',
        ]);

        $scheduledDate = Carbon::today()->addDays(2)->format('Y-m-d');

        // Accepted consultation (should be visible on calendar)
        $acceptedConsult = Consultation::create([
            'consultation_number' => 'CN-ACCEPTED-1',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $pet->id,
            'type' => 'video',
            'status' => 'accepted',
            'fee' => 750.00,
            'scheduled_at' => $scheduledDate . ' 10:00:00',
            'duration_minutes' => 30,
            'reason' => 'Routine checkup',
        ]);

        // Declined consultation (MUST be hidden from calendar)
        $declinedConsult = Consultation::create([
            'consultation_number' => 'CN-DECLINED-1',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $pet->id,
            'type' => 'video',
            'status' => 'declined',
            'decline_reason' => 'Doctor unavailable at that time slot.',
            'fee' => 750.00,
            'scheduled_at' => $scheduledDate . ' 14:00:00',
            'duration_minutes' => 30,
            'reason' => 'Declined appointment',
        ]);

        // Cancelled consultation (MUST also be hidden from calendar)
        $cancelledConsult = Consultation::create([
            'consultation_number' => 'CN-CANCELLED-1',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $pet->id,
            'type' => 'chat',
            'status' => 'cancelled_by_client',
            'fee' => 750.00,
            'scheduled_at' => $scheduledDate . ' 16:00:00',
            'duration_minutes' => 15,
            'reason' => 'Cancelled visit',
        ]);

        $response = $this->actingAs($this->vet)->get(route('vet.dashboard'));
        $response->assertOk();

        $calendarData = collect($response->viewData('calendarConsultations'));

        // Verify accepted consultation is present
        $this->assertNotNull($calendarData->firstWhere('consultation_number', 'CN-ACCEPTED-1'));

        // Verify declined and cancelled consultations are NOT in the calendar data
        $this->assertNull($calendarData->firstWhere('consultation_number', 'CN-DECLINED-1'));
        $this->assertNull($calendarData->firstWhere('consultation_number', 'CN-CANCELLED-1'));
    }

    public function test_vet_dashboard_pending_requests_endpoint_returns_json(): void
    {
        $pet = Pet::create([
            'user_id' => $this->client->id,
            'name' => 'Hector',
            'animal_type_id' => $this->dogType->id,
            'sex' => 'Male',
        ]);

        $pendingConsult = Consultation::create([
            'consultation_number' => 'CN-PENDING-REALTIME',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $pet->id,
            'type' => 'video',
            'status' => 'pending',
            'fee' => 750.00,
            'scheduled_at' => Carbon::tomorrow()->format('Y-m-d 09:00:00'),
            'duration_minutes' => 30,
            'reason' => 'Ear infection check',
        ]);

        $response = $this->actingAs($this->vet)->getJson(route('vet.dashboard.pending-requests'));

        $response->assertOk();
        $response->assertJsonStructure([
            'count',
            'pending_requests' => [
                '*' => [
                    'id',
                    'consultation_number',
                    'client_name',
                    'pet_names',
                    'animal_icon',
                    'type',
                    'scheduled_at_formatted',
                    'reason',
                    'accept_url',
                    'decline_url',
                    'show_url',
                ]
            ]
        ]);

        $this->assertEquals(1, $response->json('count'));
        $this->assertEquals('CN-PENDING-REALTIME', $response->json('pending_requests.0.consultation_number'));
        $this->assertEquals('James Wilson', $response->json('pending_requests.0.client_name'));
        $this->assertEquals('Hector', $response->json('pending_requests.0.pet_names'));
    }

    public function test_vet_can_decline_consultation_via_ajax(): void
    {
        $pet = Pet::create([
            'user_id' => $this->client->id,
            'name' => 'Hector',
            'animal_type_id' => $this->dogType->id,
            'sex' => 'Male',
        ]);

        $pendingConsult = Consultation::create([
            'consultation_number' => 'CN-TO-DECLINE',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $pet->id,
            'type' => 'video',
            'status' => 'pending',
            'fee' => 750.00,
            'scheduled_at' => Carbon::tomorrow()->format('Y-m-d 11:00:00'),
            'duration_minutes' => 30,
            'reason' => 'Checkup request',
        ]);

        $response = $this->actingAs($this->vet)->postJson(route('vet.requests.decline', $pendingConsult), [
            'decline_reason' => 'Schedule full on that morning.',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Consultation request declined.',
            'consultation_id' => $pendingConsult->id,
        ]);

        $pendingConsult->refresh();
        $this->assertEquals('declined', $pendingConsult->status);
        $this->assertEquals('Schedule full on that morning.', $pendingConsult->decline_reason);

        // Verify that after decline, it is hidden from the calendar
        $dashResponse = $this->actingAs($this->vet)->get(route('vet.dashboard'));
        $calendarData = collect($dashResponse->viewData('calendarConsultations'));
        $this->assertNull($calendarData->firstWhere('consultation_number', 'CN-TO-DECLINE'));
    }

    public function test_vet_can_toggle_availability_via_ajax_without_page_refresh(): void
    {
        // Initially vet is available = true (from setUp)
        $this->assertTrue($this->vet->vetProfile->is_available);

        // Toggle to offline via AJAX
        $response = $this->actingAs($this->vet)->postJson(route('vet.toggle-availability'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'is_available' => false,
            'message' => 'You are now offline.',
        ]);

        $this->vet->vetProfile->refresh();
        $this->assertFalse($this->vet->vetProfile->is_available);

        // Toggle back to online via AJAX
        $response2 = $this->actingAs($this->vet)->postJson(route('vet.toggle-availability'));

        $response2->assertOk();
        $response2->assertJson([
            'success' => true,
            'is_available' => true,
            'message' => 'You are now online for consultations.',
        ]);

        $this->vet->vetProfile->refresh();
        $this->assertTrue($this->vet->vetProfile->is_available);
    }

    public function test_vet_dashboard_contains_reactive_availability_toggle_controls(): void
    {
        $response = $this->actingAs($this->vet)->get(route('vet.dashboard'));

        $response->assertOk();
        $response->assertSee('@submit.prevent="toggleAvailability()"', false);
        $response->assertSee('x-text="isAvailable ? \'Go Offline\' : \'Go Online\'"', false);
        $response->assertSee('x-text="isAvailable ? \'Online for Consultations\' : \'Offline\'"', false);
        $response->assertSee('statusToast', false);
    }
}
