<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleSlotType;
use App\Models\ScheduleEntry;
use App\Models\ScheduleSlot;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleSlotController extends Controller
{
    public function store(Request $request)
    {
        abort_if(
            ! auth()->user()->schools()->wherePivot('role', 'admin')->exists(),
            403,
        );

        $validated = $request->validate([
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time'   => ['nullable', 'date_format:H:i', 'after:start_time'],
        ]);

        $position = (ScheduleSlot::max('position') ?? 0) + 1;

        ScheduleSlot::create([
            'position'   => $position,
            'label'      => (string) $position,
            'type'       => ScheduleSlotType::Slot,
            'start_time' => $validated['start_time'] ?? null,
            'end_time'   => $validated['end_time'] ?? null,
        ]);

        return back();
    }

    public function destroy(ScheduleSlot $scheduleSlot)
    {
        abort_if(
            ! auth()->user()->schools()->wherePivot('role', 'admin')->exists(),
            403,
        );

        $scheduleSlot->entries()->delete();
        $scheduleSlot->delete();

        return back();
    }

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
