<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Consultation;
use App\Models\VetAvailability;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    public function getSlots(Request $request, User $vet)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $selectedDate = Carbon::createFromFormat('Y-m-d', $request->date)->startOfDay();
        $today = Carbon::today();

        if ($selectedDate->lt($today)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot book appointments for past dates.',
                'slots' => [],
            ]);
        }

        // Check vet availability for this day of week (0=Sunday, 1=Monday... 6=Saturday)
        $dayOfWeek = $selectedDate->dayOfWeek;
        $availabilities = VetAvailability::where('user_id', $vet->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        if ($availabilities->isEmpty()) {
            return response()->json([
                'success' => true,
                'is_workday' => false,
                'message' => "Dr. {$vet->name} does not have clinic hours on {$selectedDate->format('l')}s.",
                'slots' => [],
            ]);
        }

        // Fetch existing bookings for this vet on this date that are NOT cancelled/declined/expired
        // First come, first served: pending bookings block slots immediately
        $blockingStatuses = ['pending', 'accepted', 'scheduled', 'in_progress', 'reschedule_suggested'];
        
        $existingBookings = Consultation::where('vet_id', $vet->id)
            ->whereDate('scheduled_at', $request->date)
            ->whereIn('status', $blockingStatuses)
            ->get();

        // Also check if any booking has a suggested_scheduled_at on this date
        $suggestedBookings = Consultation::where('vet_id', $vet->id)
            ->whereDate('suggested_scheduled_at', $request->date)
            ->where('status', 'reschedule_suggested')
            ->get();

        $bookedTimes = [];
        foreach ($existingBookings as $b) {
            $bookedTimes[$b->scheduled_at->format('H:i')] = [
                'status' => $b->status,
                'reason' => ($b->status === 'pending') ? 'Reserved (Pending Doctor Acceptance)' : 'Already Booked',
            ];
        }
        foreach ($suggestedBookings as $b) {
            if ($b->suggested_scheduled_at) {
                $bookedTimes[$b->suggested_scheduled_at->format('H:i')] = [
                    'status' => 'reschedule_suggested',
                    'reason' => 'Reserved (Proposed Reschedule Slot)',
                ];
            }
        }

        $allSlots = [];
        $now = Carbon::now();

        foreach ($availabilities as $avail) {
            $start = Carbon::createFromFormat('Y-m-d H:i:s', $request->date . ' ' . $avail->start_time);
            $end = Carbon::createFromFormat('Y-m-d H:i:s', $request->date . ' ' . $avail->end_time);

            $currentSlot = $start->copy();
            while ($currentSlot->lt($end)) {
                $timeKey = $currentSlot->format('H:i');
                $timeValue = $currentSlot->format('H:i:s');
                $displayTime = $currentSlot->format('g:i A');

                $isPast = $selectedDate->isToday() && $currentSlot->lt($now);
                $isBooked = isset($bookedTimes[$timeKey]);

                if ($isPast) {
                    $allSlots[] = [
                        'time' => $timeValue,
                        'display_time' => $displayTime,
                        'is_available' => false,
                        'status' => 'past',
                        'reason' => 'Time slot has passed',
                    ];
                } elseif ($isBooked) {
                    $allSlots[] = [
                        'time' => $timeValue,
                        'display_time' => $displayTime,
                        'is_available' => false,
                        'status' => $bookedTimes[$timeKey]['status'],
                        'reason' => $bookedTimes[$timeKey]['reason'],
                    ];
                } else {
                    $allSlots[] = [
                        'time' => $timeValue,
                        'display_time' => $displayTime,
                        'is_available' => true,
                        'status' => 'open',
                        'reason' => 'Available for booking',
                    ];
                }

                // Increment by 1 hour (or 30 mins)
                $currentSlot->addHour();
            }
        }

        return response()->json([
            'success' => true,
            'is_workday' => true,
            'date' => $request->date,
            'day_name' => $selectedDate->format('l, F j, Y'),
            'slots' => $allSlots,
        ]);
    }
}
