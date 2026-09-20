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
        SystemSetting::set('default_additional_pet_fee', 250.00, 'Default Additional Pet Fee (₱)', 'Default consultation fee added for each extra pet.');
        SystemSetting::set('default_additional_pet_duration', 15, 'Default Additional Pet Duration (Minutes)', 'Default time added to consultation for each extra pet.');
        SystemSetting::set('additional_pet_credits_cost', 150, 'Additional Pet Credit Cost', 'Number of additional credits required for each extra pet.');

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
