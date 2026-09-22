<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\Consultation;
use App\Models\ConsultationTimeExtension;
use App\Models\CreditTransaction;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsultationTimeExtensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        SystemSetting::set('time_extension_credits_per_minute', '5');
    }

    private function createTestConsultation(int $durationMinutes = 15, int $clientCredits = 500): Consultation
    {
        $vet = User::where('role', 'veterinarian')->first();
        $client = User::where('role', 'client')->first();
        $client->update(['credits' => $clientCredits]);

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

    public function test_doctor_can_add_complimentary_free_time_with_zero_credits_charged_to_client()
    {
        $consultation = $this->createTestConsultation(15, 500);
        $vet = $consultation->vet;
        $client = $consultation->client;

        $response = $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/doctor-add-time", [
            'minutes' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'duration_minutes' => 25,
            'remaining_seconds' => 1500, // 25 mins * 60
        ]);

        $consultation->refresh();
        $client->refresh();

        $this->assertEquals(25, $consultation->duration_minutes);
        // Client credits MUST remain unchanged (free of charge)
        $this->assertEquals(500, $client->credits);

        $this->assertDatabaseHas('consultation_time_extensions', [
            'consultation_id' => $consultation->id,
            'requested_by' => 'vet',
            'minutes' => 10,
            'credits_cost' => 0,
            'status' => 'approved',
        ]);
    }

    public function test_non_vet_cannot_add_complimentary_free_time()
    {
        $consultation = $this->createTestConsultation(15);
        $client = $consultation->client;

        $response = $this->actingAs($client)->postJson("/consultation/{$consultation->id}/doctor-add-time", [
            'minutes' => 5,
        ]);

        $response->assertStatus(403);
    }

    public function test_client_can_request_time_extension_with_credit_quote()
    {
        $consultation = $this->createTestConsultation(15, 500);
        $client = $consultation->client;

        // 10 minutes package = 50 credits
        $response = $this->actingAs($client)->postJson("/consultation/{$consultation->id}/request-extension", [
            'minutes' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'extension' => [
                'minutes' => 10,
                'credits_cost' => 50,
                'status' => 'pending',
            ]
        ]);

        // Client credits are not yet deducted while pending
        $client->refresh();
        $this->assertEquals(500, $client->credits);

        $this->assertDatabaseHas('consultation_time_extensions', [
            'consultation_id' => $consultation->id,
            'requested_by' => 'client',
            'minutes' => 10,
            'credits_cost' => 50,
            'status' => 'pending',
        ]);
    }

    public function test_all_extension_package_prices_match_configuration()
    {
        // 10 mins = 50 credits
        // 15 mins = 75 credits
        // 20 mins = 100 credits
        // 25 mins = 125 credits
        // 30 mins = 150 credits
        $this->assertEquals(50, ConsultationTimeExtension::getCostForMinutes(10));
        $this->assertEquals(75, ConsultationTimeExtension::getCostForMinutes(15));
        $this->assertEquals(100, ConsultationTimeExtension::getCostForMinutes(20));
        $this->assertEquals(125, ConsultationTimeExtension::getCostForMinutes(25));
        $this->assertEquals(150, ConsultationTimeExtension::getCostForMinutes(30));
    }

    public function test_client_cannot_request_extension_with_insufficient_credits()
    {
        // Client only has 30 credits, but 10 mins costs 50 credits
        $consultation = $this->createTestConsultation(15, 30);
        $client = $consultation->client;

        $response = $this->actingAs($client)->postJson("/consultation/{$consultation->id}/request-extension", [
            'minutes' => 10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);

        $this->assertDatabaseMissing('consultation_time_extensions', [
            'consultation_id' => $consultation->id,
            'status' => 'pending',
        ]);
    }

    public function test_client_cannot_request_duplicate_concurrent_extension()
    {
        $consultation = $this->createTestConsultation(15, 500);
        $client = $consultation->client;

        ConsultationTimeExtension::create([
            'consultation_id' => $consultation->id,
            'requested_by' => 'client',
            'minutes' => 10,
            'credits_cost' => 50,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($client)->postJson("/consultation/{$consultation->id}/request-extension", [
            'minutes' => 15,
        ]);

        $response->assertStatus(422);
    }

    public function test_doctor_can_approve_client_time_extension_and_deduct_credits()
    {
        $consultation = $this->createTestConsultation(15, 500);
        $vet = $consultation->vet;
        $client = $consultation->client;

        $extension = ConsultationTimeExtension::create([
            'consultation_id' => $consultation->id,
            'requested_by' => 'client',
            'minutes' => 10,
            'credits_cost' => 50,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/extensions/{$extension->id}/approve");

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'duration_minutes' => 25,
        ]);

        $consultation->refresh();
        $client->refresh();
        $extension->refresh();

        $this->assertEquals(25, $consultation->duration_minutes);
        $this->assertEquals('approved', $extension->status);
        // 500 - 50 = 450 credits
        $this->assertEquals(450, $client->credits);

        // Verify credit transaction was created
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $client->id,
            'amount' => -50,
            'consultation_id' => $consultation->id,
        ]);
    }

    public function test_doctor_can_decline_client_time_extension_without_deducting_credits()
    {
        $consultation = $this->createTestConsultation(15, 500);
        $vet = $consultation->vet;
        $client = $consultation->client;

        $extension = ConsultationTimeExtension::create([
            'consultation_id' => $consultation->id,
            'requested_by' => 'client',
            'minutes' => 10,
            'credits_cost' => 50,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/extensions/{$extension->id}/decline", [
            'reason' => 'Next appointment scheduled soon',
        ]);

        $response->assertStatus(200);

        $consultation->refresh();
        $client->refresh();
        $extension->refresh();

        $this->assertEquals(15, $consultation->duration_minutes);
        $this->assertEquals('declined', $extension->status);
        $this->assertEquals('Next appointment scheduled soon', $extension->decline_reason);
        // Credits must NOT be deducted
        $this->assertEquals(500, $client->credits);
    }

    public function test_client_can_cancel_own_pending_extension()
    {
        $consultation = $this->createTestConsultation(15, 500);
        $client = $consultation->client;

        $extension = ConsultationTimeExtension::create([
            'consultation_id' => $consultation->id,
            'requested_by' => 'client',
            'minutes' => 10,
            'credits_cost' => 50,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($client)->postJson("/consultation/{$consultation->id}/extensions/{$extension->id}/cancel");

        $response->assertStatus(200);

        $extension->refresh();
        $this->assertEquals('cancelled', $extension->status);
    }

    public function test_doctor_approving_extension_on_expired_or_completed_consultation_reopens_to_in_progress()
    {
        $consultation = $this->createTestConsultation(15, 500);
        $consultation->update(['status' => 'completed', 'time_consumed_seconds' => 900]);
        $vet = $consultation->vet;

        $extension = ConsultationTimeExtension::create([
            'consultation_id' => $consultation->id,
            'requested_by' => 'client',
            'minutes' => 10,
            'credits_cost' => 50,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/extensions/{$extension->id}/approve");

        $response->assertStatus(200);

        $consultation->refresh();
        $this->assertEquals('in_progress', $consultation->status);
        $this->assertEquals(25, $consultation->duration_minutes);
    }

    public function test_heartbeat_sync_returns_pending_extension()
    {
        $consultation = $this->createTestConsultation(15, 500);
        $vet = $consultation->vet;

        $extension = ConsultationTimeExtension::create([
            'consultation_id' => $consultation->id,
            'requested_by' => 'client',
            'minutes' => 10,
            'credits_cost' => 50,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/sync-time");

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'timer' => [
                'pending_extension' => [
                    'id' => $extension->id,
                    'minutes' => 10,
                    'credits_cost' => 50,
                ]
            ]
        ]);
    }

    public function test_doctor_add_free_time_via_web_form_redirects_with_success_message()
    {
        $consultation = $this->createTestConsultation(15, 500);
        $vet = $consultation->vet;

        $response = $this->actingAs($vet)->post("/consultation/{$consultation->id}/doctor-add-time", [
            'minutes' => 15,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $consultation->refresh();
        $this->assertEquals(30, $consultation->duration_minutes);
    }

    public function test_doctor_approve_extension_via_web_form_redirects_with_success_message()
    {
        $consultation = $this->createTestConsultation(15, 500);
        $vet = $consultation->vet;
        $client = $consultation->client;

        $extension = ConsultationTimeExtension::create([
            'consultation_id' => $consultation->id,
            'requested_by' => 'client',
            'minutes' => 10,
            'credits_cost' => 50,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($vet)->post("/consultation/{$consultation->id}/extensions/{$extension->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $consultation->refresh();
        $client->refresh();
        $this->assertEquals(25, $consultation->duration_minutes);
        $this->assertEquals(450, $client->credits);
    }

    public function test_admin_can_edit_extension_packages_in_settings()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->post('/admin/settings', [
            'booking_credits_cost' => 300,
            'video_call_time_limit_minutes' => 15,
            'default_additional_pet_fee' => 250.00,
            'default_additional_pet_duration' => 15,
            'additional_pet_credits_cost' => 150,
            'package_minutes' => [10, 15, 20, 25, 30, 45],
            'package_credits' => [60, 90, 120, 150, 180, 270],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $packages = ConsultationTimeExtension::getPackages();
        $this->assertCount(6, $packages);
        $this->assertEquals(60, ConsultationTimeExtension::getCostForMinutes(10));
        $this->assertEquals(270, ConsultationTimeExtension::getCostForMinutes(45));
    }

    public function test_client_credits_balance_endpoint_returns_live_credits_and_restricts_unauthorized_users()
    {
        $consultation = $this->createTestConsultation(15, 350);
        $client = $consultation->client;
        $vet = $consultation->vet;

        // Guest access must redirect to login
        $guestResponse = $this->getJson('/client/credits-balance');
        $guestResponse->assertStatus(401);

        // Vet access must be forbidden (403 for json, 302 for web)
        $vetResponse = $this->actingAs($vet)->getJson('/client/credits-balance');
        $vetResponse->assertStatus(403);

        // Client access returns current credits count and formatted string
        $clientResponse = $this->actingAs($client)->getJson('/client/credits-balance');
        $clientResponse->assertStatus(200);
        $clientResponse->assertJson([
            'status' => 'success',
            'credits' => 350,
            'formatted' => '350',
        ]);
    }

    public function test_doctor_approval_updates_realtime_credits_in_response_heartbeat_and_balance_endpoint()
    {
        $consultation = $this->createTestConsultation(15, 500);
        $vet = $consultation->vet;
        $client = $consultation->client;

        // 1. Initial client credits check
        $initialBalance = $this->actingAs($client)->getJson('/client/credits-balance');
        $initialBalance->assertJson(['credits' => 500]);

        // 2. Client requests a 10-minute extension (cost: 50 credits)
        $extension = ConsultationTimeExtension::create([
            'consultation_id' => $consultation->id,
            'requested_by' => 'client',
            'minutes' => 10,
            'credits_cost' => 50,
            'status' => 'pending',
        ]);

        // 3. Doctor approves extension: check that response contains updated client_credits (450)
        $approveResponse = $this->actingAs($vet)->postJson("/consultation/{$consultation->id}/extensions/{$extension->id}/approve");
        $approveResponse->assertStatus(200);
        $approveResponse->assertJson([
            'status' => 'success',
            'duration_minutes' => 25,
            'client_credits' => 450,
        ]);

        // 4. Consultation heartbeat sync also returns updated client_credits (450) and duration_minutes (25)
        $syncResponse = $this->actingAs($client)->postJson("/consultation/{$consultation->id}/sync-time");
        $syncResponse->assertStatus(200);
        $syncResponse->assertJson([
            'status' => 'success',
            'timer' => [
                'client_credits' => 450,
                'duration_minutes' => 25,
                'total_seconds' => 1500,
            ],
        ]);

        // 5. Ambient navbar balance poller endpoint returns deducted credits (450)
        $newBalance = $this->actingAs($client)->getJson('/client/credits-balance');
        $newBalance->assertStatus(200);
        $newBalance->assertJson([
            'status' => 'success',
            'credits' => 450,
            'formatted' => '450',
        ]);
    }
}

