<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleSlotType;
use App\Models\ScheduleSlot;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleSlotController extends Controller
{
    public function updateType(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:schedule_slots,id'],
            'type'  => ['required', Rule::enum(ScheduleSlotType::class)],
        ]);

        ScheduleSlot::whereIn('id', $validated['ids'])
            ->whereHas('schedule', fn ($q) => $q->where('user_id', $user->id))
            ->update(['type' => $validated['type']]);

        return back();
    }
}
