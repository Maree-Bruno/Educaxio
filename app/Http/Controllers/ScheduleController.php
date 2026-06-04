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
    use \App\Http\Controllers\Concerns\DetectsCurrentAcademicYear;

    public function index(Request $request)
    {
        $user = auth()->user();
        $userSchools = $user->schools()->orderBy('name')->get(['schools.id', 'schools.name', 'schools.slug']);
        $schoolIds = $userSchools->pluck('id');

        $currentYearId = $this->currentAcademicYearId($schoolIds);

        $academicYears = $this->academicYearsForSchools($schoolIds, $currentYearId);

        $selectedYearId = $this->selectedAcademicYearId($currentYearId, $request)
            ?? $academicYears->first()['id']
            ?? null;

        $scheduleQuery = Schedule::where('user_id', $user->id)
            ->orderBy('school_id');

        if ($selectedYearId) {
            $scheduleQuery->where('academic_year_id', $selectedYearId);
        }

        $schedules = $scheduleQuery->get();
        $scheduleIds = $schedules->pluck('id');

        $schoolSlotTimes = $this->schoolSlotTimes($schoolIds);

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

        $schedulesData = $schedules->map(fn ($s) => [
            'id'        => $s->id,
            'school_id' => $s->school_id,
            'school'    => $schoolsById->get($s->school_id)?->name,
        ]);

        return Inertia::render('Schedules', [
            'slots'         => $slots,
            'entries'       => $entries,
            'lessons'       => $lessons,
            'schools'       => $userSchools,
            'schedules'     => $schedulesData,
            'academicYears' => $academicYears,
            'filters'       => (object) ['year' => $selectedYearId ? (string) $selectedYearId : null],
        ]);
    }
}
