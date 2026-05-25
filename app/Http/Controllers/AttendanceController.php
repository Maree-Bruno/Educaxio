<?php

namespace App\Http\Controllers;

use App\Enums\Attendance_type;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\ScheduleEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $date = $request->string('date')->toString() ?: now()->toDateString();
        $dow = Carbon::parse($date)->dayOfWeekIso;

        // Tous les créneaux du prof ce jour-là
        $entries = ScheduleEntry::where('schedule_entries.day_of_week', $dow)
            ->whereHas('lesson.users', fn ($q) => $q->where('users.id', $user->id))
            ->join('schedule_slots', 'schedule_entries.schedule_slot_id', '=', 'schedule_slots.id')
            ->orderBy('schedule_slots.position')
            ->select('schedule_entries.*')
            ->with([
                'scheduleSlot:id,label,position',
                'lesson:id,group_id,subject_id',
                'lesson.group:id,grade,name,slug,school_id',
                'lesson.group.school:id,name,slug',
                'lesson.subject:id,name',
            ])
            ->get();

        // Sélection : ?entry=id prioritaire, puis ?group=slug (depuis ClassListShow)
        $groupSlug = $request->string('group')->toString() ?: null;
        $entryId = $request->integer('entry') ?: null;

        $selected = $entryId
            ? $entries->firstWhere('id', $entryId)
            : ($groupSlug
                ? $entries->first(fn ($e) => $e->lesson->group->slug === $groupSlug)
                : null);

        $students = collect();
        $statuses = [];
        $attendanceId = null;
        $session = null;

        if ($selected) {
            $students = $selected->lesson->group->students()
                ->orderBy('lastname')->orderBy('firstname')
                ->get(['id', 'lastname', 'firstname']);

            $session = ClassSession::where('lesson_id', $selected->lesson_id)
                ->whereDate('date', $date)->first();

            if ($session?->attendance) {
                $attendanceId = $session->attendance->id;
                $statuses = $session->attendance
                    ->studentAttendanceStatuses()
                    ->get(['student_id', 'type', 'motive'])->toArray();
            }
        }

        return Inertia::render('Attendance', [
            'entries' => $entries->map(fn ($e) => [
                'id' => $e->id,
                'label' => $e->scheduleSlot->label,
                'lesson_id' => $e->lesson_id,
                'subject' => $e->lesson->subject->name,
                'group' => $e->lesson->group->grade.$e->lesson->group->name,
                'school' => $e->lesson->group->school->name,
            ])->values(),
            'selectedEntry' => $selected?->id,
            'selectedSchool' => $selected?->lesson->group->school->name,
            'selectedGroup' => $selected ? ($selected->lesson->group->grade.$selected->lesson->group->name) : null,
            'date' => $date,
            'students' => $students,
            'statuses' => $statuses,
            'attendanceId' => $attendanceId,
            'lastSavedAt' => $session?->updated_at?->format('d/m/Y H:i'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'statuses' => ['nullable', 'array'],
            'statuses.*.student_id' => ['required', 'exists:students,id'],
            'statuses.*.type' => ['required', Rule::in(Attendance_type::values())],
            'statuses.*.motive' => ['nullable', 'string', 'max:500'],
        ]);

        $session = ClassSession::firstOrCreate(
            ['lesson_id' => $validated['lesson_id'], 'date' => $validated['date']],
        );

        $attendance = Attendance::firstOrCreate(
            ['classsession_id' => $session->id],
        );

        $attendance->studentAttendanceStatuses()->delete();

        foreach ($validated['statuses'] ?? [] as $status) {
            $attendance->studentAttendanceStatuses()->create($status);
        }

        $session->touch();

        return back();
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return back();
    }
}
