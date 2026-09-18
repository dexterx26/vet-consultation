<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\Consultation;
use App\Models\SystemSetting;
use App\Models\VetAvailability;
use App\Models\CreditTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsultationCreditsAndBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_update_platform_settings()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->post('/admin/settings', [
            'booking_credits_cost' => 350,
            'video_call_time_limit_minutes' => 2,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(350, SystemSetting::get('booking_credits_cost'));
        $this->assertEquals(2, SystemSetting::get('video_call_time_limit_minutes'));

        // Reset to default
        SystemSetting::set('booking_credits_cost', 300);
        SystemSetting::set('video_call_time_limit_minutes', 1);
    }

    public function test_admin_can_add_credits_to_client()
    {
        $admin = User::where('role', 'admin')->first();
        $client = User::where('role', 'client')->first();
        $initialCredits = $client->credits ?? 0;

        $response = $this->actingAs($admin)->post("/admin/users/{$client->id}/credits", [
            'amount' => 300,
            'notes' => 'Test admin top-up',
        ]);

        $response->assertSessionHas('success');
        $client->refresh();
        $this->assertEquals($initialCredits + 300, $client->credits);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $client->id,
            'amount' => 300,
            'type' => 'admin_topup',
        ]);
    }

    public function test_slot_availability_blocks_pending_and_accepted_bookings_in_real_time()
    {
        $client = User::where('role', 'client')->first();
        $vet = User::where('role', 'veterinarian')->where('status', 'active')->first();
        $pet = Pet::where('user_id', $client->id)->first();

        // Ensure vet has availability on a future weekday
        $targetDate = Carbon::now()->addDays(2);
        while ($targetDate->isSunday()) {
            $targetDate->addDay();
        }
        $dateStr = $targetDate->format('Y-m-d');
        $dayOfWeek = $targetDate->dayOfWeek;

        VetAvailability::updateOrCreate(
            ['user_id' => $vet->id, 'day_of_week' => $dayOfWeek],
            ['start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => true]
        );

        // Before booking, 10:00:00 should be open
        $resBefore = $this->actingAs($client)->getJson("/client/vets/{$vet->id}/slots?date={$dateStr}");
        $resBefore->assertOk();
        $dataBefore = $resBefore->json();
        $slot10Before = collect($dataBefore['slots'])->firstWhere('time', '10:00:00');
        $this->assertNotNull($slot10Before);
        $this->assertTrue($slot10Before['is_available']);

        // Create a PENDING booking for 10:00:00
        $consultation = Consultation::create([
            'consultation_number' => 'TEST-' . rand(1000, 9999),
            'client_id' => $client->id,
            'vet_id' => $vet->id,
            'pet_id' => $pet->id,
            'type' => 'video',
            'status' => 'pending',
            'scheduled_at' => "{$dateStr} 10:00:00",
            'fee' => 500.00,
            'reason' => 'Testing slot reservation locking',
        ]);

        // After booking, 10:00:00 MUST now be blocked in real-time ("who comes first, even if dr has not confirmed yet")
        $resAfter = $this->actingAs($client)->getJson("/client/vets/{$vet->id}/slots?date={$dateStr}");
        $resAfter->assertOk();
        $dataAfter = $resAfter->json();
        $slot10After = collect($dataAfter['slots'])->firstWhere('time', '10:00:00');
        $this->assertNotNull($slot10After);
        $this->assertFalse($slot10After['is_available']);
        $this->assertEquals('pending', $slot10After['status']);

        // Clean up test consultation
        $consultation->delete();
    }

    public function test_booking_confirmation_deducts_credits()
    {
        $client = User::where('role', 'client')->first();
        $vet = User::where('role', 'veterinarian')->where('status', 'active')->first();
        $pet = Pet::where('user_id', $client->id)->first();

        // Ensure client has credits
        $client->update(['credits' => 600]);

        $consultation = Consultation::create([
            'consultation_number' => 'TEST-CONFIRM-' . rand(1000, 9999),
            'client_id' => $client->id,
            'vet_id' => $vet->id,
            'pet_id' => $pet->id,
            'type' => 'video',
            'status' => 'pending',
            'scheduled_at' => Carbon::now()->addDays(3)->format('Y-m-d 14:00:00'),
            'fee' => 500.00,
            'credits_deducted' => 0,
            'reason' => 'Testing deduction on confirmation',
        ]);

        // Vet confirms / accepts the booking
        $response = $this->actingAs($vet)->post("/vet/requests/{$consultation->id}/accept");
        $response->assertSessionHas('success');

        $consultation->refresh();
        $client->refresh();

        // Status must be accepted
        $this->assertEquals('accepted', $consultation->status);
        // Credits deducted must be 300
        $this->assertEquals(300, $consultation->credits_deducted);
        // Client credits must be 600 - 300 = 300
        $this->assertEquals(300, $client->credits);

        $consultation->delete();
    }

    public function test_vet_suggest_reschedule_and_client_confirmation_flow()
    {
        $client = User::where('role', 'client')->first();
        $vet = User::where('role', 'veterinarian')->where('status', 'active')->first();
        $pet = Pet::where('user_id', $client->id)->first();

        $client->update(['credits' => 600]);

        $originalTime = Carbon::now()->addDays(3)->format('Y-m-d 09:00:00');
        $suggestedDate = Carbon::now()->addDays(4)->format('Y-m-d');
        $suggestedTime = '14:00:00';

        $consultation = Consultation::create([
            'consultation_number' => 'TEST-RESCHED-' . rand(1000, 9999),
            'client_id' => $client->id,
            'vet_id' => $vet->id,
            'pet_id' => $pet->id,
            'type' => 'video',
            'status' => 'pending',
            'scheduled_at' => $originalTime,
            'fee' => 500.00,
            'credits_deducted' => 0,
            'reason' => 'Testing doctor reschedule flow',
        ]);

        // Doctor suggests another time slot
        $vetResponse = $this->actingAs($vet)->post("/vet/requests/{$consultation->id}/suggest-reschedule", [
            'suggested_date' => $suggestedDate,
            'suggested_time' => $suggestedTime,
            'reschedule_note' => 'I have a surgery in the morning, let us do 2:00 PM instead.',
        ]);
        $vetResponse->assertSessionHas('success');

        $consultation->refresh();
        $this->assertEquals('reschedule_suggested', $consultation->status);
        $this->assertEquals("{$suggestedDate} {$suggestedTime}", $consultation->suggested_scheduled_at->format('Y-m-d H:i:s'));
        // No credits deducted yet!
        $this->assertEquals(0, $consultation->credits_deducted);
        $this->assertEquals(600, $client->fresh()->credits);

        // Client accepts the new schedule
        $clientResponse = $this->actingAs($client)->post("/client/bookings/{$consultation->id}/accept-reschedule");
        $clientResponse->assertSessionHas('success');

        $consultation->refresh();
        $client->refresh();

        // Status is now accepted, scheduled_at is updated, and 300 credits deducted
        $this->assertEquals('accepted', $consultation->status);
        $this->assertEquals("{$suggestedDate} {$suggestedTime}", $consultation->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertEquals(300, $consultation->credits_deducted);
        $this->assertEquals(300, $client->credits);

        $consultation->delete();
    }
}
