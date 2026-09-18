<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\CreditTransaction;
use Illuminate\Database\Seeder;

class CreditsAndSettingsSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::set('booking_credits_cost', 300, 'Booking Credit Cost', 'Number of credits deducted when a booking is confirmed.');
        SystemSetting::set('video_call_time_limit_minutes', 1, 'Video Call Limit (Minutes)', 'Duration limit for video teleconsultation calls.');

        $clients = User::where('role', 'client')->get();
        foreach ($clients as $client) {
            if ($client->credits == 0) {
                $client->update(['credits' => 900]);
                CreditTransaction::create([
                    'user_id' => $client->id,
                    'amount' => 900,
                    'type' => 'admin_topup',
                    'balance_after' => 900,
                    'notes' => 'Initial welcome credits for testing',
                ]);
            }
        }
    }
}
