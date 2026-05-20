<?php

namespace App\Http\Controllers;

use App\Models\ScheduleEntry;
use App\Models\ScheduleSlot;
use Illuminate\Http\Request;

class ScheduleEntryController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'lesson_id'   => ['required', 'integer', 'exists:lessons,id'],
            'position'    => ['required', 'integer', 'min:1'],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:5'],
            'classroom'   => ['nullable', 'string', 'max:50'],
        ]);

        $lesson = $user->lessons()->with('group:id,school_id')->findOrFail($validated['lesson_id']);

        $slot = ScheduleSlot::where('position', $validated['position'])
            ->whereHas('schedule', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('school_id', $lesson->group->school_id)
            )
            ->firstOrFail();

        ScheduleEntry::updateOrCreate(
            ['schedule_slot_id' => $slot->id, 'day_of_week' => $validated['day_of_week']],
            ['lesson_id' => $lesson->id, 'classroom' => $validated['classroom']],
        );

        return back();
    }

    public function destroy(ScheduleEntry $scheduleEntry)
    {
        abort_unless(
            $scheduleEntry->scheduleSlot->schedule->user_id === auth()->id(),
            403
        );

        $scheduleEntry->delete();

        return back();
    }
}
