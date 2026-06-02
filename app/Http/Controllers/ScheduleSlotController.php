<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleSlotType;
use App\Models\ScheduleEntry;
use App\Models\ScheduleSlot;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleSlotController extends Controller
{
    public function updateType(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:schedule_slots,id'],
            'type' => ['required', Rule::enum(ScheduleSlotType::class)],
        ]);

        $slot = ScheduleSlot::findOrFail($validated['id']);
        $slot->update(['type' => $validated['type']]);

        if ($validated['type'] === ScheduleSlotType::Lunch->value) {
            ScheduleEntry::where('schedule_slot_id', $slot->id)->delete();
        } else {
            ScheduleEntry::withTrashed()->where('schedule_slot_id', $slot->id)->restore();
        }

        return back();
    }
}
