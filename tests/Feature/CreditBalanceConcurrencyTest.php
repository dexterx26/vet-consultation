<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreditBalanceConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_credits_balance_endpoint_uses_cache_under_repeated_queries()
    {
        $client = User::where('role', 'client')->first();
        $this->assertNotNull($client);

        // First call populates cache
        $firstResponse = $this->actingAs($client)->getJson('/client/credits-balance');
        $firstResponse->assertOk();
        $firstResponse->assertJson(['credits' => (int) $client->credits]);

        // Clear query log and do repeated calls to simulate concurrent polling
        DB::flushQueryLog();
        DB::enableQueryLog();

        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($client)->getJson('/client/credits-balance');
            $response->assertOk();
            $response->assertJson(['credits' => (int) $client->credits]);
        }

        $queries = DB::getQueryLog();
        // None of the cached calls should execute a fresh user query for credits
        $freshUserQueries = array_filter($queries, function ($q) {
            return str_contains($q['query'], 'users') && str_contains($q['query'], 'where `id` =');
        });

        // The cached balance should not issue individual fresh user selects
        $this->assertEmpty($freshUserQueries, 'Repeated credits-balance calls should be served directly from cache.');
    }

    public function test_credits_cache_is_invalidated_when_user_credits_are_modified()
    {
        $client = User::where('role', 'client')->first();
        $initialCredits = (int) $client->credits;

        // Populate initial cache
        $this->actingAs($client)->getJson('/client/credits-balance')
            ->assertJson(['credits' => $initialCredits]);

        // Add credits to user
        $client->addCredits(250, 'Test top up');

        // Immediately check endpoint without waiting for TTL
        $updatedResponse = $this->actingAs($client)->getJson('/client/credits-balance');
        $updatedResponse->assertOk();
        $updatedResponse->assertJson([
            'credits' => $initialCredits + 250,
            'formatted' => number_format($initialCredits + 250),
        ]);

        // Deduct credits from user
        $client->deductCredits(100, null, 'Test deduction');

        // Check endpoint again
        $deductedResponse = $this->actingAs($client)->getJson('/client/credits-balance');
        $deductedResponse->assertOk();
        $deductedResponse->assertJson([
            'credits' => $initialCredits + 150,
            'formatted' => number_format($initialCredits + 150),
        ]);
    }

    public function test_app_layout_contains_adaptive_polling_and_visibility_listeners()
    {
        $client = User::where('role', 'client')->first();

        $response = $this->actingAs($client)->get('/client/dashboard');
        $response->assertOk();

        // Check for Page Visibility API protection and randomized jitter
        $response->assertSee('!document.hidden', false);
        $response->assertSee('visibilitychange', false);
        $response->assertSee('lastFetchedAt', false);
    }
}
