<?php

namespace Tests\Feature;

use App\Events\NewConsultationRequest;
use App\Models\AnimalType;
use App\Models\Breed;
use App\Models\Consultation;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetAvailability;
use App\Models\VetProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class VetDashboardReverbBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private User $vet;
    private User $otherVet;
    private User $client;
    private AnimalType $dogType;
    private Breed $breed;
    private Pet $pet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vet = User::factory()->create([
            'role' => 'veterinarian',
            'status' => 'active',
            'name' => 'Dr. Stephen Strange, DVM',
            'credits' => 0,
        ]);

        VetProfile::create([
            'user_id' => $this->vet->id,
            'license_number' => 'PRC-VET-99999',
            'clinic_name' => 'Sanctum Vet Care',
            'clinic_address' => '177A Bleecker St, New York',
            'years_experience' => 10,
            'animals_handled' => ['Dog', 'Cat'],
            'consultation_fee' => 500.00,
            'additional_pet_fee' => 200.00,
            'additional_pet_duration' => 15,
            'is_available' => true,
        ]);

        // Add availability for vet for all days
        for ($i = 0; $i <= 6; $i++) {
            VetAvailability::create([
                'user_id' => $this->vet->id,
                'day_of_week' => $i,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
            ]);
        }

        $this->otherVet = User::factory()->create([
            'role' => 'veterinarian',
            'status' => 'active',
            'name' => 'Dr. Tony Stark, DVM',
        ]);

        $this->client = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
            'name' => 'Peter Parker',
            'credits' => 1000,
        ]);

        $this->dogType = AnimalType::create([
            'name' => 'Dog',
            'icon' => 'fa-dog',
            'is_active' => true,
        ]);

        $this->breed = Breed::create([
            'animal_type_id' => $this->dogType->id,
            'name' => 'Golden Retriever',
        ]);

        $this->pet = Pet::create([
            'user_id' => $this->client->id,
            'animal_type_id' => $this->dogType->id,
            'breed_id' => $this->breed->id,
            'name' => 'MayDog',
            'sex' => 'Female',
        ]);
    }

    public function test_booking_consultation_dispatches_new_consultation_request_event(): void
    {
        Event::fake([NewConsultationRequest::class]);

        $scheduledDate = Carbon::tomorrow()->format('Y-m-d');
        $scheduledTime = '10:00:00';

        $response = $this->actingAs($this->client)->post(route('client.bookings.store'), [
            'vet_id' => $this->vet->id,
            'pet_id' => $this->pet->id,
            'type' => 'video',
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $scheduledTime,
            'reason' => 'Routine checkup and flea inspection',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('consultations', [
            'vet_id' => $this->vet->id,
            'client_id' => $this->client->id,
            'status' => 'pending',
            'type' => 'video',
        ]);

        Event::assertDispatched(NewConsultationRequest::class, function ($event) {
            $channels = $event->broadcastOn();
            $data = $event->broadcastWith();

            return count($channels) === 1
                && $channels[0]->name === 'private-vet.' . $this->vet->id
                && $event->broadcastAs() === 'consultation.requested'
                && $data['client_name'] === 'Peter Parker'
                && $data['pet_names'] === 'MayDog'
                && $data['type'] === 'video'
                && $data['status'] === 'pending';
        });
    }

    public function test_new_consultation_request_event_broadcast_data_structure(): void
    {
        $consultation = Consultation::create([
            'consultation_number' => 'VET-TEST1234',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $this->pet->id,
            'type' => 'chat',
            'status' => 'pending',
            'scheduled_at' => Carbon::now()->addDays(2),
            'fee' => 500.00,
            'duration_minutes' => 15,
            'reason' => 'Skin irritation on belly',
        ]);
        $consultation->pets()->attach($this->pet->id, ['is_primary' => true]);

        $event = new NewConsultationRequest($consultation);
        $channels = $event->broadcastOn();
        $data = $event->broadcastWith();

        $this->assertEquals('consultation.requested', $event->broadcastAs());
        $this->assertEquals('private-vet.' . $this->vet->id, $channels[0]->name);
        $this->assertEquals($consultation->id, $data['id']);
        $this->assertEquals('VET-TEST1234', $data['consultation_number']);
        $this->assertEquals('Peter Parker', $data['client_name']);
        $this->assertEquals('MayDog', $data['pet_names']);
        $this->assertEquals('chat', $data['type']);
        $this->assertEquals('pending', $data['status']);
        $this->assertNotEmpty($data['scheduled_at_formatted']);
        $this->assertNotEmpty($data['accept_url']);
        $this->assertNotEmpty($data['show_url']);
    }

    public function test_vet_channel_authorization(): void
    {
        $callback = Broadcast::getChannels()->get('vet.{vetId}');
        $this->assertNotNull($callback);

        // Target vet accessing their own channel succeeds
        $this->assertTrue((bool) $callback($this->vet, $this->vet->id));

        // Another vet accessing target vet's channel is forbidden
        $this->assertFalse((bool) $callback($this->otherVet, $this->vet->id));

        // Client accessing vet channel is forbidden
        $this->assertFalse((bool) $callback($this->client, $this->vet->id));
    }

    public function test_vet_dashboard_contains_reverb_realtime_setup(): void
    {
        $response = $this->actingAs($this->vet)->get(route('vet.dashboard'));

        $response->assertOk();
        $response->assertSee('window.vetPendingRequests =', false);
        $response->assertSee('window.vetUserId = ' . $this->vet->id, false);
        $response->assertSee('setupEcho', false);
        $response->assertSee('.consultation.requested', false);
        $response->assertSee('handleNewConsultationRequest', false);
        $response->assertSee('handleConsultationCancelled', false);
        $response->assertSee('.consultation.cancelled', false);
        $response->assertSee('Reverb Live', false);
        $response->assertSee('New Consultation Request!', false);
        // Verify aggressive polling timer was removed in favor of Reverb WebSockets
        $response->assertDontSee('pollingTimer = setInterval', false);
    }

    public function test_cancelling_consultation_dispatches_consultation_cancelled_event(): void
    {
        Event::fake([\App\Events\ConsultationCancelled::class]);

        $consultation = Consultation::create([
            'consultation_number' => 'VET-CANCEL-TEST',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $this->pet->id,
            'type' => 'video',
            'status' => 'pending',
            'scheduled_at' => Carbon::now()->addDays(2),
            'fee' => 500.00,
            'duration_minutes' => 15,
            'reason' => 'Checkup',
        ]);
        $consultation->pets()->attach($this->pet->id, ['is_primary' => true]);

        $response = $this->actingAs($this->client)->post(route('client.bookings.cancel', $consultation));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'cancelled_by_client',
        ]);

        Event::assertDispatched(\App\Events\ConsultationCancelled::class, function ($event) use ($consultation) {
            $channels = $event->broadcastOn();
            $data = $event->broadcastWith();

            $hasVetChannel = collect($channels)->contains(fn ($ch) => $ch->name === 'private-vet.' . $this->vet->id);

            return $hasVetChannel
                && $event->broadcastAs() === 'consultation.cancelled'
                && $data['id'] === $consultation->id
                && $data['status'] === 'cancelled_by_client';
        });
    }
}
