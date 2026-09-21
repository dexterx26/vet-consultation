<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\Consultation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsultationTimeDeductionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createTestConsultation(int $durationMinutes = 15): Consultation
    {
        $vet = User::where('role', 'veterinarian')->first();
        $client = User::where('role', 'client')->first();
        $pet = Pet::first() ?: Pet::create([
            'user_id' => $client->id,
            'name' => 'Fluffy',
            'animal_type_id' => 1,
            'sex' => 'Male',
        ]);

        return Consultation::create([
            'consultation_number' => 'TEST-' . uniqid(),
            'client_id' => $client->id,
            'vet_id' => $vet->id,
            'pet_id' => $pet->id,
            'type' => 'video',
            'status' => 'accepted',
            'scheduled_at' => now()->addHour(),
            'fee' => 500.00,
            'duration_minutes' => $durationMinutes,
            'reason' => 'Routine health checkup',
        ]);
    }

    public function test_client_entering_alone_does_not_deduct_time()
    {
        $consultation = $this->createTestConsultation(15);
        $client = $consultation->client;

        // Client enters and syncs time
        $response = $this->actingAs($client)->postJson("/consultation/{$consultation->id}/sync-time");

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'timer' => [
                'total_seconds' => 900,
                'consumed_seconds' => 0,
                'remaining_seconds' => 900,
                'doctor_present' => false,
                'client_present' => true,
                'is_timer_running' => false,
            ]
        ]);

        $consultation->refresh();
        $this->assertEquals(0, $consultation->time_consumed_seconds);
        $this->assertNotNull($consultation->client_joined_at);
        $this->assertNull($consultation->doctor_joined_at);
        $this->assertFalse($consultation->isTimerRunning());
    }

    public function test_time_deducts_when_doctor_enters()
    {
        $consultation = $this->createTestConsultation(15);
        $vet = $consultation->vet;

        $baseTime = Carbon::create(2026, 9, 21, 10, 0, 0);
        Carbon::setTestNow($baseTime);

        // Doctor enters room (first heartbeat sets session start)
        $response1 = $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/sync-time");
        $response1->assertStatus(200);
        $response1->assertJson([
            'status' => 'success',
            'timer' => [
                'consumed_seconds' => 0,
                'doctor_present' => true,
                'is_timer_running' => true,
            ]
        ]);

        $consultation->refresh();
        $this->assertNotNull($consultation->doctor_joined_at);
        $this->assertEquals('in_progress', $consultation->status);

        // Advance 3 seconds (standard heartbeat interval)
        Carbon::setTestNow($baseTime->copy()->addSeconds(3));
        $response2 = $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/sync-time");
        $response2->assertStatus(200);

        $consultation->refresh();
        $this->assertEquals(3, $consultation->time_consumed_seconds);
        $this->assertEquals(897, $consultation->remaining_seconds);
    }

    public function test_if_client_did_not_enter_deductions_are_saved_in_database()
    {
        $consultation = $this->createTestConsultation(15);
        $vet = $consultation->vet;

        $baseTime = Carbon::create(2026, 9, 21, 10, 0, 0);
        Carbon::setTestNow($baseTime);

        // Client has not entered! Only vet is in the room.
        $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/sync-time");

        // Doctor stays in room for 10 seconds across heartbeats
        Carbon::setTestNow($baseTime->copy()->addSeconds(5));
        $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/sync-time");

        Carbon::setTestNow($baseTime->copy()->addSeconds(10));
        $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/sync-time");

        $consultation->refresh();
        $this->assertEquals(10, $consultation->time_consumed_seconds);
        $this->assertNull($consultation->client_joined_at);

        // Deductions are strictly persisted in the DB table
        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'time_consumed_seconds' => 10,
            'client_joined_at' => null,
        ]);
    }

    public function test_resuming_consultation_only_consumes_remaining_time()
    {
        $consultation = $this->createTestConsultation(15);
        $vet = $consultation->vet;
        $client = $consultation->client;

        // Simulate 300 seconds (5 minutes) already consumed while doctor was waiting
        $consultation->update([
            'time_consumed_seconds' => 300,
            'doctor_joined_at' => now()->subMinutes(10),
            'status' => 'in_progress',
        ]);

        // Client now opens the consultation
        $clientResponse = $this->actingAs($client)->postJson("/consultation/{$consultation->id}/sync-time");
        $clientResponse->assertStatus(200);
        $clientResponse->assertJson([
            'timer' => [
                'total_seconds' => 900,
                'consumed_seconds' => 300,
                'remaining_seconds' => 600, // Exactly 10 minutes remaining!
                'formatted_remaining' => '10:00',
                'formatted_consumed' => '05:00',
            ]
        ]);

        // When vet resumes, time continues deducting from the remaining 600 seconds
        $baseTime = Carbon::create(2026, 9, 21, 10, 0, 0);
        Carbon::setTestNow($baseTime);
        $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/sync-time");

        Carbon::setTestNow($baseTime->copy()->addSeconds(4));
        $vetResponse = $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/sync-time");

        $vetResponse->assertJson([
            'timer' => [
                'consumed_seconds' => 304,
                'remaining_seconds' => 596,
            ]
        ]);

        $consultation->refresh();
        $this->assertEquals(304, $consultation->time_consumed_seconds);
        $this->assertEquals(596, $consultation->remaining_seconds);
    }

    public function test_live_chat_fetches_messages_and_syncs_timer()
    {
        $consultation = $this->createTestConsultation(15);
        $vet = $consultation->vet;
        $client = $consultation->client;

        // Vet enters live chat
        $baseTime = Carbon::create(2026, 9, 21, 10, 0, 0);
        Carbon::setTestNow($baseTime);
        $vetChatResponse = $this->actingAs($vet)->getJson("/consultation/{$consultation->id}/messages");
        $vetChatResponse->assertStatus(200);
        $vetChatResponse->assertJsonStructure([
            'status',
            'timer' => [
                'total_seconds',
                'consumed_seconds',
                'remaining_seconds',
                'doctor_present',
                'is_timer_running',
            ],
            'messages',
        ]);

        $this->assertTrue($vetChatResponse->json('timer.doctor_present'));
        $this->assertTrue($vetChatResponse->json('timer.is_timer_running'));

        // Advance 3 seconds in live chat
        Carbon::setTestNow($baseTime->copy()->addSeconds(3));
        $vetChatResponse2 = $this->actingAs($vet)->getJson("/consultation/{$consultation->id}/messages");
        $this->assertEquals(3, $vetChatResponse2->json('timer.consumed_seconds'));

        // Client polling chat receives the synced timer
        $clientChatResponse = $this->actingAs($client)->getJson("/consultation/{$consultation->id}/messages");
        $this->assertEquals(3, $clientChatResponse->json('timer.consumed_seconds'));
        $this->assertEquals(897, $clientChatResponse->json('timer.remaining_seconds'));
    }

    public function test_messages_blocked_when_consultation_time_expires()
    {
        $consultation = $this->createTestConsultation(1); // 1 minute consultation (60 seconds)
        $client = $consultation->client;

        // Mark consultation as fully consumed
        $consultation->update([
            'time_consumed_seconds' => 60,
        ]);

        $this->assertEquals(0, $consultation->remaining_seconds);
        $this->assertTrue($consultation->isExpired());

        // Attempting to send message should be blocked with 403
        $response = $this->actingAs($client)->postJson("/consultation/{$consultation->id}/messages", [
            'message' => 'Hello doctor after expiry',
        ]);

        $response->assertStatus(403);
    }

    public function test_offline_gap_is_not_deducted_when_doctor_disconnects_and_rejoins()
    {
        $consultation = $this->createTestConsultation(15);
        $vet = $consultation->vet;

        $baseTime = Carbon::create(2026, 9, 21, 10, 0, 0);
        Carbon::setTestNow($baseTime);

        // Doctor enters, spends 6 seconds
        $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/sync-time");
        Carbon::setTestNow($baseTime->copy()->addSeconds(6));
        $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/sync-time");

        $consultation->refresh();
        $this->assertEquals(6, $consultation->time_consumed_seconds);

        // Doctor closes browser and goes away for 30 minutes (1800 seconds)
        Carbon::setTestNow($baseTime->copy()->addSeconds(1806));

        // Doctor rejoins (first ping after gap resets interval start without deducting the gap)
        $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/sync-time");
        $consultation->refresh();
        $this->assertEquals(6, $consultation->time_consumed_seconds); // Gap was NOT deducted!

        // Doctor spends 4 more seconds
        Carbon::setTestNow($baseTime->copy()->addSeconds(1810));
        $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/sync-time");

        $consultation->refresh();
        $this->assertEquals(10, $consultation->time_consumed_seconds);
        $this->assertEquals(890, $consultation->remaining_seconds);
    }
}
