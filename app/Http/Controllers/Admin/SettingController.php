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
        $defaultAdditionalPetFee = SystemSetting::get('default_additional_pet_fee', 250.00);
        $defaultAdditionalPetDuration = SystemSetting::get('default_additional_pet_duration', 15);
        $additionalPetCreditsCost = SystemSetting::get('additional_pet_credits_cost', 150);
        $extensionPackages = \App\Models\ConsultationTimeExtension::getPackages();

        return view('admin.settings.index', compact(
            'bookingCreditsCost',
            'videoCallTimeLimit',
            'defaultAdditionalPetFee',
            'defaultAdditionalPetDuration',
            'additionalPetCreditsCost',
            'extensionPackages'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'booking_credits_cost' => 'required|integer|min:1|max:10000',
            'video_call_time_limit_minutes' => 'required|integer|min:1|max:120',
            'default_additional_pet_fee' => 'nullable|numeric|min:0',
            'default_additional_pet_duration' => 'nullable|integer|min:1|max:120',
            'additional_pet_credits_cost' => 'nullable|integer|min:0|max:10000',
            'package_minutes' => 'nullable|array',
            'package_minutes.*' => 'nullable|integer|min:1|max:300',
            'package_credits' => 'nullable|array',
            'package_credits.*' => 'nullable|integer|min:0|max:100000',
        ]);

        SystemSetting::set('booking_credits_cost', $request->booking_credits_cost, 'Booking Credit Cost', 'Number of credits deducted when a booking is confirmed.');
        SystemSetting::set('video_call_time_limit_minutes', $request->video_call_time_limit_minutes, 'Video Call Limit (Minutes)', 'Duration limit for video teleconsultation calls.');

        if ($request->filled('default_additional_pet_fee')) {
            SystemSetting::set('default_additional_pet_fee', $request->default_additional_pet_fee, 'Default Additional Pet Fee (₱)', 'Default consultation fee added for each extra pet.');
        }

        if ($request->filled('default_additional_pet_duration')) {
            SystemSetting::set('default_additional_pet_duration', $request->default_additional_pet_duration, 'Default Additional Pet Duration (Minutes)', 'Default time added to consultation for each extra pet.');
        }

        if ($request->filled('additional_pet_credits_cost')) {
            SystemSetting::set('additional_pet_credits_cost', $request->additional_pet_credits_cost, 'Additional Pet Credit Cost', 'Number of additional credits required for each extra pet.');
        }

        if ($request->has('package_minutes') && is_array($request->package_minutes)) {
            $packages = [];
            $minutes = $request->package_minutes;
            $credits = $request->package_credits ?? [];

            foreach ($minutes as $index => $min) {
                $minVal = (int) $min;
                $creditVal = isset($credits[$index]) ? (int) $credits[$index] : 0;
                if ($minVal > 0 && $creditVal >= 0) {
                    $packages[] = [
                        'minutes' => $minVal,
                        'credits' => $creditVal,
                    ];
                }
            }

            if (!empty($packages)) {
                usort($packages, fn($a, $b) => $a['minutes'] <=> $b['minutes']);
                SystemSetting::set(
                    'time_extension_packages',
                    json_encode($packages),
                    'Time Extension Packages',
                    'Configured extension duration tiers and credit costs'
                );
            }
        }

        return back()->with('success', 'System platform settings updated successfully!');
    }
}
