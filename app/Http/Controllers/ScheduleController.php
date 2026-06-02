<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\ScheduleSlot;
use App\Models\SchoolSlotTime;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $userSchools = $user->schools()->orderBy('name')->get(['schools.id', 'schools.name', 'schools.slug']);
        $schoolIds = $userSchools->pluck('id');

        $academicYears = AcademicYear::whereHas('schools', fn ($q) => $q->whereIn('schools.id', $schoolIds))
            ->orderByDesc('year')
            ->get(['id', 'year']);

        $selectedYearId = $request->filled('year')
            ? $request->integer('year')
            : $academicYears->first()?->id;

        $scheduleQuery = Schedule::where('user_id', $user->id)
            ->orderBy('school_id');

        if ($selectedYearId) {
            $scheduleQuery->where('academic_year_id', $selectedYearId);
        }

        $schedules = $scheduleQuery->get();
        $scheduleIds = $schedules->pluck('id');

        // Per-school slot time overrides
        $schoolSlotTimes = SchoolSlotTime::whereIn('school_id', $schoolIds)
            ->get()
            ->groupBy('school_id')
            ->map(fn ($rows) => $rows->keyBy('schedule_slot_id')->map(fn ($r) => [
                'start_time' => substr($r->start_time, 0, 5),
                'end_time' => substr($r->end_time, 0, 5),
            ]));

        // Use the first school that has configured slot times, else any school
        $indicatorSchoolId = $schoolSlotTimes->keys()->first() ?? $userSchools->first()?->id;
        $slotTimesForIndicator = $schoolSlotTimes[$indicatorSchoolId] ?? collect();

        $slots = ScheduleSlot::orderBy('position')
            ->get()
            ->map(function ($slot) use ($slotTimesForIndicator) {
                $override = $slotTimesForIndicator[$slot->id] ?? null;
                $globalStart = $slot->start_time ? substr($slot->start_time, 0, 5) : null;
                $globalEnd = $slot->end_time ? substr($slot->end_time, 0, 5) : null;

                return [
                    'id' => $slot->id,
                    'position' => $slot->position,
                    'label' => $slot->label,
                    'type' => $slot->type->value,
                    'start_time' => $override['start_time'] ?? $globalStart,
                    'end_time' => $override['end_time'] ?? $globalEnd,
                ];
            });

        $lessons = $user->lessons()
            ->with([
                'group:id,grade,name,school_id',
                'subject:id,name',
            ])
            ->whereHas('group', fn ($q) => $q->whereIn('school_id', $schoolIds))
            ->get(['id', 'group_id', 'subject_id', 'lm_level']);

        $schoolsById = $userSchools->keyBy('id');
        $lessons->each(fn ($lesson) => $lesson->group->setRelation(
            'school',
            $schoolsById->get($lesson->group->school_id),
        ));

        $entries = [];
        if ($scheduleIds->isNotEmpty()) {
            ScheduleEntry::whereIn('schedule_id', $scheduleIds)
                ->with(['scheduleSlot:id,position'])
                ->get()
                ->each(function (ScheduleEntry $e) use (&$entries, $lessons) {
                    $lesson = $lessons->firstWhere('id', $e->lesson_id);
                    if (! $lesson) {
                        return;
                    }
                    $pos = $e->scheduleSlot->position;
                    $entries[$pos][$e->day_of_week] = [
                        'id' => $e->id,
                        'lesson_id' => $e->lesson_id,
                        'grade' => $lesson->group->grade.$lesson->group->name,
                        'subject' => $lesson->subjectLabel(),
                        'room' => $e->classroom,
                        'school' => $lesson->group->school->name,
                    ];
                });
        }

        return Inertia::render('Schedules', [
            'slots' => $slots,
            'entries' => $entries,
            'lessons' => $lessons,
            'schools' => $userSchools,
            'schedules' => $schedules->map(fn ($s) => [
                'id' => $s->id,
                'school_id' => $s->school_id,
                'school' => $schoolsById->get($s->school_id)?->name,
            ]),
            'academicYears' => $academicYears,
            'filters' => (object) ['year' => $selectedYearId ? (string) $selectedYearId : null],
        ]);
    }
}
