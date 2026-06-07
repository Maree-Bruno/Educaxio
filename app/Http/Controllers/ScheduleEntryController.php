<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\ScheduleSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleEntryController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'position' => ['required', 'integer', 'min:1'],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:5'],
            'classroom' => ['nullable', 'string', 'max:50'],
            'delete_entry_id' => ['nullable', 'integer', 'exists:schedule_entries,id'],
        ]);

        $lesson = $user->lessons()->with('group:id,school_id')->findOrFail($validated['lesson_id']);

        $slot = ScheduleSlot::where('position', $validated['position'])
            ->firstOrFail();

        $schedule = Schedule::where('id', $validated['schedule_id'])
            ->where('user_id', $user->id)
            ->where('school_id', $lesson->group->school_id)
            ->firstOrFail();

        DB::transaction(function () use ($validated, $schedule, $slot, $lesson, $user) {
            if ($validated['delete_entry_id'] ?? null) {
                ScheduleEntry::whereHas('schedule', fn ($q) => $q->where('user_id', $user->id))
                    ->where('id', $validated['delete_entry_id'])
                    ->forceDelete();
            }

            $entry = ScheduleEntry::withTrashed()
                ->where('schedule_id', $schedule->id)
                ->where('schedule_slot_id', $slot->id)
                ->where('day_of_week', $validated['day_of_week'])
                ->first();

            if ($entry) {
                if ($entry->trashed()) {
                    $entry->restore();
                }
                $entry->update(['lesson_id' => $lesson->id, 'classroom' => $validated['classroom']]);
            } else {
                ScheduleEntry::create([
                    'schedule_id' => $schedule->id,
                    'schedule_slot_id' => $slot->id,
                    'day_of_week' => $validated['day_of_week'],
                    'lesson_id' => $lesson->id,
                    'classroom' => $validated['classroom'],
                ]);
            }
        });

        return to_route('schedules');
    }

    public function destroy(ScheduleEntry $scheduleEntry)
    {
        $this->authorize('delete', $scheduleEntry);

        $scheduleEntry->forceDelete();

        return to_route('schedules');
    }
}
