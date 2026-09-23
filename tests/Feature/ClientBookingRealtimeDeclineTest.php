<?php

namespace Tests\Feature;

use App\Events\ConsultationDeclined;
use App\Models\AnimalType;
use App\Models\Breed;
use App\Models\Consultation;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ClientBookingRealtimeDeclineTest extends TestCase
{
    use RefreshDatabase;

    private User $vet;
    private User $client;
    private User $otherClient;
    private Pet $pet;
    private Consultation $consultation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vet = User::factory()->create([
            'role' => 'veterinarian',
            'status' => 'active',
            'name' => 'Dr. Maria Santos, DVM',
        ]);

        VetProfile::create([
            'user_id' => $this->vet->id,
            'license_number' => 'PRC-VET-12345',
            'clinic_name' => 'Santos Animal Wellness Clinic',
            'clinic_address' => 'Makati City, Metro Manila',
            'years_experience' => 8,
            'animals_handled' => ['Dog', 'Cat'],
            'consultation_fee' => 500.00,
            'is_available' => true,
        ]);

        $this->client = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
            'name' => 'John Doe',
            'credits' => 1000,
        ]);

        $this->otherClient = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
            'name' => 'Jane Smith',
            'credits' => 500,
        ]);

        $dogType = AnimalType::create([
            'name' => 'Dog',
            'icon' => 'fa-dog',
            'is_active' => true,
        ]);

        $breed = Breed::create([
            'animal_type_id' => $dogType->id,
            'name' => 'Golden Retriever',
        ]);

        $this->pet = Pet::create([
            'user_id' => $this->client->id,
            'animal_type_id' => $dogType->id,
            'breed_id' => $breed->id,
            'name' => 'Max',
            'sex' => 'Male',
        ]);

        $this->consultation = Consultation::create([
            'consultation_number' => 'VET-DECLINE01',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $this->pet->id,
            'type' => 'video',
            'status' => 'pending',
            'scheduled_at' => Carbon::tomorrow()->setHour(14)->setMinute(0),
            'fee' => 500.00,
            'duration_minutes' => 15,
            'reason' => 'Persistent cough and low energy',
        ]);
        $this->consultation->pets()->attach($this->pet->id, ['is_primary' => true]);
    }

    public function test_doctor_declining_consultation_dispatches_consultation_declined_event(): void
    {
        Event::fake([ConsultationDeclined::class]);

        $declineReason = 'Emergency surgical procedure scheduled at this hour. Please rebook for tomorrow.';

        $response = $this->actingAs($this->vet)->post(route('vet.requests.decline', $this->consultation), [
            'decline_reason' => $declineReason,
        ]);

        $response->assertSessionHas('info');

        $this->assertDatabaseHas('consultations', [
            'id' => $this->consultation->id,
            'status' => 'declined',
            'decline_reason' => $declineReason,
        ]);

        Event::assertDispatched(ConsultationDeclined::class, function ($event) use ($declineReason) {
            $channels = $event->broadcastOn();
            $data = $event->broadcastWith();

            $hasConsultationChannel = collect($channels)->contains(fn ($ch) => $ch->name === 'private-consultation.' . $this->consultation->id);
            $hasClientUserChannel = collect($channels)->contains(fn ($ch) => $ch->name === 'private-App.Models.User.' . $this->client->id);

            return $hasConsultationChannel
                && $hasClientUserChannel
                && $event->broadcastAs() === 'consultation.declined'
                && $data['id'] === $this->consultation->id
                && $data['status'] === 'declined'
                && $data['decline_reason'] === $declineReason
                && $data['vet_name'] === 'Dr. Maria Santos, DVM';
        });
    }

    public function test_consultation_declined_event_structure_and_channels(): void
    {
        $this->consultation->update([
            'status' => 'declined',
            'decline_reason' => 'Unavailable at requested time',
        ]);

        $event = new ConsultationDeclined($this->consultation);
        $channels = $event->broadcastOn();
        $payload = $event->broadcastWith();

        $this->assertEquals('consultation.declined', $event->broadcastAs());
        $this->assertEquals('private-consultation.' . $this->consultation->id, $channels[0]->name);
        $this->assertEquals('private-App.Models.User.' . $this->client->id, $channels[1]->name);

        $this->assertEquals($this->consultation->id, $payload['id']);
        $this->assertEquals('declined', $payload['status']);
        $this->assertEquals('Unavailable at requested time', $payload['decline_reason']);
        $this->assertEquals('Dr. Maria Santos, DVM', $payload['vet_name']);
        $this->assertEquals($this->client->id, $payload['client_id']);
        $this->assertNotEmpty($payload['fetch_url']);
    }

    public function test_consultation_channel_authorization_allows_client_and_vet(): void
    {
        $callback = Broadcast::getChannels()->get('consultation.{consultationId}');
        $this->assertNotNull($callback);

        // Client who booked can access
        $this->assertTrue((bool) $callback($this->client, $this->consultation->id));

        // Assigned Vet can access
        $this->assertTrue((bool) $callback($this->vet, $this->consultation->id));

        // Other client CANNOT access
        $this->assertFalse((bool) $callback($this->otherClient, $this->consultation->id));
    }

    public function test_client_bookings_status_endpoint_returns_json_and_decline_reason(): void
    {
        $this->consultation->update([
            'status' => 'declined',
            'decline_reason' => 'Doctor called in for clinic emergency',
        ]);

        $response = $this->actingAs($this->client)->getJson(route('client.bookings.status', $this->consultation));

        $response->assertOk();
        $response->assertJson([
            'id' => $this->consultation->id,
            'consultation_number' => 'VET-DECLINE01',
            'status' => 'declined',
            'status_label' => 'Declined',
            'decline_reason' => 'Doctor called in for clinic emergency',
            'vet_name' => 'Dr. Maria Santos, DVM',
        ]);
    }

    public function test_client_cannot_access_another_clients_booking_status(): void
    {
        $response = $this->actingAs($this->otherClient)->getJson(route('client.bookings.status', $this->consultation));

        $response->assertStatus(403);
    }

    public function test_client_booking_show_page_renders_reverb_listener_and_decline_elements(): void
    {
        $response = $this->actingAs($this->client)->get(route('client.bookings.show', $this->consultation));

        $response->assertOk();
        $response->assertSee('clientBookingShowComponent', false);
        $response->assertSee('consultationId: ' . $this->consultation->id, false);
        $response->assertSee('consultation.${this.consultationId}', false);
        $response->assertSee('.consultation.declined', false);
        $response->assertSee('handleDeclined', false);
        $response->assertSee('fetchStatus', false);
        $response->assertSee('Consultation Request Declined', false);
        $response->assertSee('No credits were deducted', false);
        $response->assertSee('Book Another Vet', false);
    }
}
