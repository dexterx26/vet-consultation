<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Models\Breed;
use App\Models\Consultation;
use App\Models\ConsultationCall;
use App\Models\ConsultationMessage;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationEndChatTest extends TestCase
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
            'license_number' => 'VET-12345',
            'years_of_experience' => 5,
            'consultation_fee' => 50.00,
            'is_verified' => true,
        ]);

        $this->client = User::factory()->create(['role' => 'client', 'credits' => 100]);
        $this->stranger = User::factory()->create(['role' => 'client']);

        $animalType = AnimalType::create(['name' => 'Dog', 'slug' => 'dog']);
        $breed = Breed::create(['animal_type_id' => $animalType->id, 'name' => 'Golden Retriever']);

        $pet = Pet::create([
            'user_id' => $this->client->id,
            'animal_type_id' => $animalType->id,
            'breed_id' => $breed->id,
            'name' => 'Buddy',
            'sex' => 'Male',
        ]);

        $this->consultation = Consultation::create([
            'consultation_number' => 'CN-' . strtoupper(uniqid()),
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $pet->id,
            'status' => 'in_progress',
            'type' => 'chat',
            'fee' => 50.00,
            'reason' => 'Routine checkup',
            'duration_minutes' => 15,
            'time_consumed_seconds' => 120,
            'scheduled_at' => now(),
            'doctor_joined_at' => now()->subMinutes(2),
            'doctor_last_seen_at' => now(),
            'client_joined_at' => now()->subMinutes(2),
            'client_last_seen_at' => now(),
        ]);
    }

    public function test_vet_can_end_chat_consultation_marking_teleconsultation_completed()
    {
        $response = $this->actingAs($this->vet)->post("/consultation/{$this->consultation->id}/chat/end");

        $response->assertRedirect(route('vet.records.create', $this->consultation));
        $response->assertSessionHas('info');

        $this->consultation->refresh();
        $this->assertEquals('completed', $this->consultation->status);

        // System message created
        $this->assertDatabaseHas('consultation_messages', [
            'consultation_id' => $this->consultation->id,
            'sender_id' => $this->vet->id,
        ]);

        $systemMsg = ConsultationMessage::where('consultation_id', $this->consultation->id)->latest()->first();
        $this->assertStringContainsString('Consultation ended by Dr.', $systemMsg->message);

        // Notification created for client
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->client->id,
            'title' => 'Consultation Ended',
        ]);
    }

    public function test_client_can_end_chat_consultation_marking_teleconsultation_completed()
    {
        $response = $this->actingAs($this->client)->post("/consultation/{$this->consultation->id}/chat/end");

        $response->assertRedirect(route('client.bookings.show', $this->consultation));
        $response->assertSessionHas('info');

        $this->consultation->refresh();
        $this->assertEquals('completed', $this->consultation->status);

        // Notification created for vet
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->vet->id,
            'title' => 'Consultation Ended',
        ]);
    }

    public function test_unauthorized_user_cannot_end_chat()
    {
        $response = $this->actingAs($this->stranger)->post("/consultation/{$this->consultation->id}/chat/end");
        $response->assertStatus(403);

        $this->consultation->refresh();
        $this->assertEquals('in_progress', $this->consultation->status);
    }

    public function test_ending_chat_also_ends_associated_call_session()
    {
        $call = ConsultationCall::create([
            'consultation_id' => $this->consultation->id,
            'room_name' => 'room_' . $this->consultation->consultation_number,
            'room_token' => 'token123',
            'status' => 'waiting',
        ]);

        $response = $this->actingAs($this->vet)->post("/consultation/{$this->consultation->id}/chat/end");
        $response->assertRedirect(route('vet.records.create', $this->consultation));

        $call->refresh();
        $this->assertEquals('ended', $call->status);
        $this->assertNotNull($call->ended_at);
    }

    public function test_messages_blocked_after_chat_is_ended()
    {
        $this->actingAs($this->vet)->post("/consultation/{$this->consultation->id}/chat/end");

        $sendResponse = $this->actingAs($this->client)->postJson("/consultation/{$this->consultation->id}/messages", [
            'message' => 'Hello, can you still hear me?',
        ]);

        $sendResponse->assertStatus(403);
    }

    public function test_vet_ending_video_call_also_marks_consultation_completed()
    {
        $response = $this->actingAs($this->vet)->post("/consultation/{$this->consultation->id}/video/end");

        $response->assertRedirect(route('vet.records.create', $this->consultation));

        $this->consultation->refresh();
        $this->assertEquals('completed', $this->consultation->status);
    }

    public function test_ended_consultation_does_not_continue_deducting_time_and_zeros_remaining_time()
    {
        // 1. Consultation starts with 120 consumed seconds out of 900 (780 seconds left)
        $this->assertEquals(120, $this->consultation->time_consumed_seconds);
        $this->assertEquals(780, $this->consultation->remaining_seconds);

        // 2. Doctor ends chat before time expires
        $this->actingAs($this->vet)->post("/consultation/{$this->consultation->id}/chat/end");
        $this->consultation->refresh();
        $this->assertEquals('completed', $this->consultation->status);

        // Remaining time is made ZERO by consuming total allotted duration (900s)
        $this->assertEquals(900, $this->consultation->time_consumed_seconds);
        $this->assertEquals(0, $this->consultation->remaining_seconds);

        // 3. Heartbeats occur 3 seconds later while doctor is on page
        $this->travel(3)->seconds();
        $syncResponse = $this->actingAs($this->vet)->postJson("/consultation/{$this->consultation->id}/sync-time");

        $syncResponse->assertStatus(200);
        $syncResponse->assertJson([
            'status' => 'success',
            'timer' => [
                'consumed_seconds' => 900,
                'remaining_seconds' => 0,
                'is_timer_running' => false,
                'consultation_status' => 'completed',
            ],
        ]);

        // 4. Verify database consumed seconds has NOT changed and remaining stays 0
        $this->consultation->refresh();
        $this->assertEquals(900, $this->consultation->time_consumed_seconds);
        $this->assertEquals(0, $this->consultation->remaining_seconds);

        // 5. Client requests to add time again (+10 minutes = 600s, 50 credits)
        $extResponse = $this->actingAs($this->client)->postJson("/consultation/{$this->consultation->id}/request-extension", [
            'minutes' => 10,
        ]);
        $extResponse->assertStatus(200);
        $extensionId = $extResponse->json('extension.id');

        // 6. Doctor approves the extension
        $approveResponse = $this->actingAs($this->vet)->postJson("/consultation/{$this->consultation->id}/extensions/{$extensionId}/approve");
        $approveResponse->assertStatus(200);

        // 7. Remaining time is calculated from ZERO plus the selected additional time (600 seconds)
        $this->consultation->refresh();
        $this->assertEquals('in_progress', $this->consultation->status);
        $this->assertEquals(25, $this->consultation->duration_minutes);
        $this->assertEquals(900, $this->consultation->time_consumed_seconds);
        $this->assertEquals(600, $this->consultation->remaining_seconds); // exactly 10 minutes remaining!
    }

    public function test_vet_ending_video_call_zeros_remaining_time_and_adds_from_zero()
    {
        // 1. Initial 120 consumed seconds out of 900
        $this->assertEquals(120, $this->consultation->time_consumed_seconds);

        // 2. Doctor ends video call
        $this->actingAs($this->vet)->post("/consultation/{$this->consultation->id}/video/end");
        $this->consultation->refresh();

        $this->assertEquals('completed', $this->consultation->status);
        $this->assertEquals(900, $this->consultation->time_consumed_seconds);
        $this->assertEquals(0, $this->consultation->remaining_seconds);

        // 3. Doctor adds complimentary time (+15 mins = 900s)
        $addResponse = $this->actingAs($this->vet)->postJson("/consultation/{$this->consultation->id}/doctor-add-time", [
            'minutes' => 15,
        ]);
        $addResponse->assertStatus(200);

        // 4. Consultation reopens and remaining time is exactly 0 + 15 mins (900 seconds)
        $this->consultation->refresh();
        $this->assertEquals('in_progress', $this->consultation->status);
        $this->assertEquals(30, $this->consultation->duration_minutes);
        $this->assertEquals(900, $this->consultation->time_consumed_seconds);
        $this->assertEquals(900, $this->consultation->remaining_seconds); // exactly 15 mins
    }

    public function test_chat_seen_receipt_marks_read_at_when_other_user_reads_messages()
    {
        // 1. Client sends message
        $sendResponse = $this->actingAs($this->client)->postJson("/consultation/{$this->consultation->id}/messages", [
            'message' => 'Hello doctor, my pet is unwell.',
        ]);
        $sendResponse->assertStatus(200);
        $sendResponse->assertJson([
            'status' => 'success',
            'data' => [
                'is_me' => true,
                'is_read' => false,
                'read_at' => null,
            ],
        ]);
        $messageId = $sendResponse->json('data.id');

        // 2. Before doctor reads it, client polls messages: is_read is false
        $clientFetch = $this->actingAs($this->client)->getJson("/consultation/{$this->consultation->id}/messages");
        $clientFetch->assertStatus(200);
        $clientMsg = collect($clientFetch->json('messages'))->firstWhere('id', $messageId);
        $this->assertTrue($clientMsg['is_me']);
        $this->assertFalse($clientMsg['is_read']);
        $this->assertNull($clientMsg['read_at']);

        // 3. Doctor enters/polls chat: marks client's message as read
        $vetFetch = $this->actingAs($this->vet)->getJson("/consultation/{$this->consultation->id}/messages");
        $vetFetch->assertStatus(200);
        $vetSeenMsg = collect($vetFetch->json('messages'))->firstWhere('id', $messageId);
        $this->assertFalse($vetSeenMsg['is_me']);
        $this->assertTrue($vetSeenMsg['is_read']);
        $this->assertNotNull($vetSeenMsg['read_at']);

        // 4. Client polls again: now sees message is marked Seen with read_at timestamp!
        $clientFetch2 = $this->actingAs($this->client)->getJson("/consultation/{$this->consultation->id}/messages");
        $clientMsg2 = collect($clientFetch2->json('messages'))->firstWhere('id', $messageId);
        $this->assertTrue($clientMsg2['is_me']);
        $this->assertTrue($clientMsg2['is_read']);
        $this->assertNotNull($clientMsg2['read_at']);

        // 5. Database record has read_at populated
        $dbMsg = ConsultationMessage::find($messageId);
        $this->assertNotNull($dbMsg->read_at);
    }
}

