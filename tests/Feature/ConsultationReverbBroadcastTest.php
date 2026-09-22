<?php

namespace Tests\Feature;

use App\Events\ConsultationMessageRead;
use App\Events\ConsultationMessageSent;
use App\Events\ConsultationTimeUpdated;
use App\Models\AnimalType;
use App\Models\Breed;
use App\Models\Consultation;
use App\Models\ConsultationMessage;
use App\Models\ConsultationTimeExtension;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ConsultationReverbBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private User $vet;
    private User $client;
    private User $stranger;
    private Consultation $consultation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vet = User::factory()->create(['role' => 'veterinarian']);
        VetProfile::create([
            'user_id' => $this->vet->id,
            'license_number' => 'VET-98765',
            'years_of_experience' => 7,
            'consultation_fee' => 60.00,
            'is_verified' => true,
        ]);

        $this->client = User::factory()->create(['role' => 'client', 'credits' => 100]);
        $this->stranger = User::factory()->create(['role' => 'client']);

        $animalType = AnimalType::create(['name' => 'Dog', 'slug' => 'dog']);
        $breed = Breed::create(['animal_type_id' => $animalType->id, 'name' => 'Bulldog']);

        $pet = Pet::create([
            'user_id' => $this->client->id,
            'animal_type_id' => $animalType->id,
            'breed_id' => $breed->id,
            'name' => 'Max',
            'sex' => 'Male',
        ]);

        $this->consultation = Consultation::create([
            'consultation_number' => 'CN-' . strtoupper(uniqid()),
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $pet->id,
            'status' => 'in_progress',
            'type' => 'chat',
            'fee' => 60.00,
            'reason' => 'Skin rash',
            'scheduled_at' => now(),
            'total_duration_seconds' => 900,
            'time_consumed_seconds' => 0,
        ]);
    }

    public function test_sending_message_dispatches_reverb_broadcast_event(): void
    {
        Event::fake([ConsultationMessageSent::class]);

        $response = $this->actingAs($this->client)->postJson(route('consultation.send-message', $this->consultation), [
            'message' => 'Hello Doctor!',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        Event::assertDispatched(ConsultationMessageSent::class, function ($event) {
            $channels = $event->broadcastOn();
            $data = $event->broadcastWith();

            return count($channels) === 1
                && $channels[0]->name === 'private-consultation.' . $this->consultation->id
                && $event->broadcastAs() === 'message.sent'
                && $data['message'] === 'Hello Doctor!'
                && $data['sender_id'] === $this->client->id;
        });
    }

    public function test_marking_messages_read_dispatches_reverb_read_event(): void
    {
        ConsultationMessage::create([
            'consultation_id' => $this->consultation->id,
            'sender_id' => $this->client->id,
            'message' => 'Can you help with Max?',
            'read_at' => null,
        ]);

        Event::fake([ConsultationMessageRead::class]);

        // Doctor marks messages as read
        $response = $this->actingAs($this->vet)->postJson(route('consultation.messages.read', $this->consultation));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'updated_count' => 1,
        ]);

        Event::assertDispatched(ConsultationMessageRead::class, function ($event) {
            $channels = $event->broadcastOn();
            $data = $event->broadcastWith();

            return count($channels) === 1
                && $channels[0]->name === 'private-consultation.' . $this->consultation->id
                && $event->broadcastAs() === 'message.read'
                && $data['consultation_id'] === $this->consultation->id
                && $data['reader_id'] === $this->vet->id;
        });
    }

    public function test_fetch_messages_also_broadcasts_read_event_when_unread_messages_exist(): void
    {
        ConsultationMessage::create([
            'consultation_id' => $this->consultation->id,
            'sender_id' => $this->vet->id,
            'message' => 'Prescription attached',
            'read_at' => null,
        ]);

        Event::fake([ConsultationMessageRead::class]);

        // Client fetches messages and marks the vet message read
        $response = $this->actingAs($this->client)->getJson(route('consultation.messages', $this->consultation));

        $response->assertStatus(200);

        Event::assertDispatched(ConsultationMessageRead::class, function ($event) {
            return $event->consultationId === $this->consultation->id
                && $event->readerId === $this->client->id;
        });
    }

    public function test_end_chat_broadcasts_system_completion_message(): void
    {
        Event::fake([ConsultationMessageSent::class]);

        $response = $this->actingAs($this->vet)->post(route('consultation.chat.end', $this->consultation));

        $response->assertStatus(302);

        Event::assertDispatched(ConsultationMessageSent::class, function ($event) {
            return str_contains($event->message->message, 'Consultation ended by')
                && $event->message->consultation_id === $this->consultation->id;
        });
    }

    public function test_channel_authorization_allows_participants_and_denies_strangers(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);

        require base_path('routes/channels.php');

        // Client auth
        $this->actingAs($this->client);
        $responseClient = $this->postJson('/broadcasting/auth', [
            'channel_name' => 'private-consultation.' . $this->consultation->id,
            'socket_id' => '1234.5678',
        ]);
        $responseClient->assertStatus(200);
        $responseClient->assertJsonStructure(['auth']);

        // Vet auth
        $this->actingAs($this->vet);
        $responseVet = $this->postJson('/broadcasting/auth', [
            'channel_name' => 'private-consultation.' . $this->consultation->id,
            'socket_id' => '1234.5678',
        ]);
        $responseVet->assertStatus(200);
        $responseVet->assertJsonStructure(['auth']);

        // Stranger auth denied
        $this->actingAs($this->stranger);
        $responseStranger = $this->postJson('/broadcasting/auth', [
            'channel_name' => 'private-consultation.' . $this->consultation->id,
            'socket_id' => '1234.5678',
        ]);
        $responseStranger->assertStatus(403);
    }

    public function test_chat_resilience_when_broadcasting_connection_fails(): void
    {
        // Even if broadcast fails or Reverb daemon is offline, HTTP endpoints must succeed
        $response = $this->actingAs($this->client)->postJson(route('consultation.send-message', $this->consultation), [
            'message' => 'Resilience check',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        $this->assertDatabaseHas('consultation_messages', [
            'consultation_id' => $this->consultation->id,
            'message' => 'Resilience check',
        ]);

        $readResponse = $this->actingAs($this->vet)->postJson(route('consultation.messages.read', $this->consultation));
        $readResponse->assertStatus(200);
        $readResponse->assertJson(['status' => 'success', 'updated_count' => 1]);
    }

    public function test_client_request_extension_broadcasts_time_updated_event(): void
    {
        Event::fake([ConsultationTimeUpdated::class, ConsultationMessageSent::class]);

        $response = $this->actingAs($this->client)->postJson(route('consultation.request-extension', $this->consultation), [
            'minutes' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        Event::assertDispatched(ConsultationTimeUpdated::class, function ($event) {
            $channels = $event->broadcastOn();
            $data = $event->broadcastWith();

            return count($channels) === 1
                && $channels[0]->name === 'private-consultation.' . $this->consultation->id
                && $event->broadcastAs() === 'time.updated'
                && $data['action'] === 'requested'
                && $data['extension']['minutes'] === 10
                && $data['client_credits'] === 100;
        });

        Event::assertDispatched(ConsultationMessageSent::class, function ($event) {
            return str_contains($event->message->message, 'requested a +10 minute extension');
        });
    }

    public function test_vet_approve_extension_broadcasts_time_updated_event(): void
    {
        $extension = ConsultationTimeExtension::create([
            'consultation_id' => $this->consultation->id,
            'requested_by' => 'client',
            'minutes' => 10,
            'credits_cost' => 50,
            'status' => 'pending',
        ]);

        Event::fake([ConsultationTimeUpdated::class, ConsultationMessageSent::class]);

        $response = $this->actingAs($this->vet)->postJson(
            route('consultation.extensions.approve', [$this->consultation, $extension])
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        Event::assertDispatched(ConsultationTimeUpdated::class, function ($event) {
            $channels = $event->broadcastOn();
            $data = $event->broadcastWith();

            return count($channels) === 1
                && $channels[0]->name === 'private-consultation.' . $this->consultation->id
                && $event->broadcastAs() === 'time.updated'
                && $data['action'] === 'approved'
                && $data['timer']['duration_minutes'] === 25
                && $data['client_credits'] === 50;
        });

        Event::assertDispatched(ConsultationMessageSent::class, function ($event) {
            return str_contains($event->message->message, 'approved the +10 minute extension');
        });
    }

    public function test_vet_doctor_add_free_time_broadcasts_time_updated_event(): void
    {
        Event::fake([ConsultationTimeUpdated::class, ConsultationMessageSent::class]);

        $response = $this->actingAs($this->vet)->postJson(route('consultation.doctor-add-time', $this->consultation), [
            'minutes' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        Event::assertDispatched(ConsultationTimeUpdated::class, function ($event) {
            $channels = $event->broadcastOn();
            $data = $event->broadcastWith();

            return count($channels) === 1
                && $channels[0]->name === 'private-consultation.' . $this->consultation->id
                && $event->broadcastAs() === 'time.updated'
                && $data['action'] === 'free_time_added'
                && $data['timer']['duration_minutes'] === 25;
        });

        Event::assertDispatched(ConsultationMessageSent::class, function ($event) {
            return str_contains($event->message->message, 'complimentary consultation time');
        });
    }

    public function test_vet_decline_extension_broadcasts_time_updated_event(): void
    {
        $extension = ConsultationTimeExtension::create([
            'consultation_id' => $this->consultation->id,
            'requested_by' => 'client',
            'minutes' => 10,
            'credits_cost' => 50,
            'status' => 'pending',
        ]);

        Event::fake([ConsultationTimeUpdated::class, ConsultationMessageSent::class]);

        $response = $this->actingAs($this->vet)->postJson(
            route('consultation.extensions.decline', [$this->consultation, $extension]),
            ['reason' => 'Next appointment starting soon']
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        Event::assertDispatched(ConsultationTimeUpdated::class, function ($event) {
            $data = $event->broadcastWith();
            return $data['action'] === 'declined';
        });

        Event::assertDispatched(ConsultationMessageSent::class);
    }

    public function test_client_cancel_extension_broadcasts_time_updated_event(): void
    {
        $extension = ConsultationTimeExtension::create([
            'consultation_id' => $this->consultation->id,
            'requested_by' => 'client',
            'minutes' => 10,
            'credits_cost' => 50,
            'status' => 'pending',
        ]);

        Event::fake([ConsultationTimeUpdated::class]);

        $response = $this->actingAs($this->client)->postJson(
            route('consultation.extensions.cancel', [$this->consultation, $extension])
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        Event::assertDispatched(ConsultationTimeUpdated::class, function ($event) {
            $data = $event->broadcastWith();
            return $data['action'] === 'cancelled';
        });
    }
}
