<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $bookingCreditsCost = SystemSetting::get('booking_credits_cost', 300);
        $videoCallTimeLimit = SystemSetting::get('video_call_time_limit_minutes', 1);

        return view('admin.settings.index', compact('bookingCreditsCost', 'videoCallTimeLimit'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'booking_credits_cost' => 'required|integer|min:1|max:10000',
            'video_call_time_limit_minutes' => 'required|integer|min:1|max:120',
        ]);

        SystemSetting::set('booking_credits_cost', $request->booking_credits_cost, 'Booking Credit Cost', 'Number of credits deducted when a booking is confirmed.');
        SystemSetting::set('video_call_time_limit_minutes', $request->video_call_time_limit_minutes, 'Video Call Limit (Minutes)', 'Duration limit for video teleconsultation calls.');

        return back()->with('success', 'System platform settings updated successfully!');
    }
}
