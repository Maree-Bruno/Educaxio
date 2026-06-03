<?php /** @noinspection D */

namespace App\Http\Controllers;

use App\Enums\Attendance_type;
use App\Http\Controllers\Concerns\ComputesNextOccurrence;
use App\Http\Controllers\Concerns\DetectsCurrentAcademicYear;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Lesson;
use App\Models\LessonNote;
use App\Models\ScheduleEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    use ComputesNextOccurrence;
    use DetectsCurrentAcademicYear;

    public function index(Request $request)
    {
        $user = auth()->user();
        $date = $request->string('date')->toString() ?: now()->toDateString();
        $dow = Carbon::parse($date)->dayOfWeekIso;

        $schoolIds = $user->schools()->pluck('schools.id');
        $currentYearId = $this->currentAcademicYearId($schoolIds);
        $selectedYearId = $request->filled('year') ? $request->integer('year') : $currentYearId;

        $academicYears = \App\Models\AcademicYear::whereHas('schools', fn ($q) => $q->whereIn('schools.id', $schoolIds))
            ->with(['schools' => fn ($q) => $q->whereIn('schools.id', $schoolIds)])
            ->orderByDesc('year')
            ->get(['id', 'year'])
            ->map(fn ($y) => [
                'id'         => $y->id,
                'year'       => $y->year,
                'is_current' => $y->id === $currentYearId,
                'is_archived' => $y->schools->every(fn ($s) => $s->pivot->archived_at !== null),
            ]);

        // Tous les créneaux du prof ce jour-là
        $entries = ScheduleEntry::where('schedule_entries.day_of_week', $dow)
            ->whereHas('lesson.users', fn ($q) => $q->where('users.id', $user->id))
            ->when($selectedYearId, fn ($q) => $q->whereHas('schedule', fn ($s) =>
                $s->where('academic_year_id', $selectedYearId)
            ))
            ->join('schedule_slots', 'schedule_entries.schedule_slot_id', '=', 'schedule_slots.id')
            ->orderBy('schedule_slots.position')
            ->select('schedule_entries.*')
            ->with([
                'scheduleSlot:id,label,position,start_time,end_time',
                'lesson:id,group_id,subject_id,lm_level',
                'lesson.group:id,grade,name,slug,school_id',
                'lesson.group.school:id,name,slug',
                'lesson.subject:id,name',
            ])
            ->get();

        // Sélection : ?creneau=slug prioritaire, puis ?group=slug (depuis ClassListShow)
        $groupSlug = $request->string('group')->toString() ?: null;
        $creneauSlug = $request->string('creneau')->toString() ?: null;

        $entrySlug = fn ($e) => Str::slug($e->lesson->group->slug.'-'.$e->scheduleSlot->label);

        $selected = $creneauSlug
            ? $entries->first(fn ($e) => $entrySlug($e) === $creneauSlug)
            : ($groupSlug
                ? $entries->first(fn ($e) => $e->lesson->group->slug === $groupSlug)
                : null);

        // Si group demandé mais pas trouvé aujourd'hui → rediriger vers la prochaine séance
        if (! $selected && $groupSlug && ! $creneauSlug) {
            $groupDows = ScheduleEntry::whereHas('lesson.users', fn ($q) => $q->where('users.id', $user->id))
                ->whereHas('lesson.group', fn ($q) => $q->where('slug', $groupSlug))
                ->when($selectedYearId, fn ($q) => $q->whereHas('schedule', fn ($s) =>
                    $s->where('academic_year_id', $selectedYearId)
                ))
                ->pluck('day_of_week');

            if ($groupDows->isNotEmpty()) {
                $cursor = Carbon::parse($date)->subDay();
                for ($i = 0; $i < 7; $i++) {
                    if ($groupDows->contains($cursor->dayOfWeekIso)) {
                        return redirect()->route('attendances', [
                            'date' => $cursor->toDateString(),
                            'group' => $groupSlug,
                        ]);
                    }
                    $cursor->subDay();
                }
            }
        }
        if (! $selected && $entries->isNotEmpty() && $date === now()->toDateString()) {
            $now = now()->format('H:i');

            $selected = $entries->first(function ($e) use ($now) {
                $start = $e->scheduleSlot->start_time ? substr($e->scheduleSlot->start_time, 0, 5) : null;
                $end = $e->scheduleSlot->end_time ? substr($e->scheduleSlot->end_time, 0, 5) : null;

                return $start && $end && $now >= $start && $now <= $end;
            });
        }

        $students = collect();
        $statuses = [];
        $attendanceId = null;
        $session = null;

        $assignments = collect();
        $nextAssignmentDate = null;

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

            $lesson = $selected->lesson->load('scheduleEntries.scheduleSlot');

            $assignments = Assignment::where('lesson_id', $selected->lesson_id)
                ->orderBy('scheduled_date')
                ->get(['id', 'type', 'title', 'scheduled_date', 'description']);

            $nextAssignmentDate = $this->nextOccurrence($lesson);

            $lessonNote = LessonNote::where('lesson_id', $selected->lesson_id)
                ->whereDate('date', $date)->first();

            $schedulePattern = $lesson->scheduleEntries
                ->sortBy('scheduleSlot.position')
                ->map(fn ($e) => [
                    'day_of_week' => $e->day_of_week,
                    'slot_label' => $e->scheduleSlot->label,
                ])->values();
        }

        $isAdmin = $user->schools()->wherePivot('role', 'admin')->exists();

        return Inertia::render('Attendance', [
            'academicYears' => $academicYears,
            'isAdmin' => $isAdmin,
            'filters' => ['year' => $selectedYearId ? (string) $selectedYearId : null],
            'entries' => $entries->map(fn ($e) => [
                'creneau' => Str::slug($e->lesson->group->slug.'-'.$e->scheduleSlot->label),
                'label' => $e->scheduleSlot->label,
                'lesson_id' => $e->lesson_id,
                'subject' => $e->lesson->subjectLabel(),
                'group' => $e->lesson->group->grade.$e->lesson->group->name,
                'school' => $e->lesson->group->school->name,
            ])->values(),
            'selectedEntry' => $selected ? Str::slug($selected->lesson->group->slug.'-'.$selected->scheduleSlot->label) : null,
            'selectedSchool' => $selected?->lesson->group->school->name,
            'selectedGroup' => $selected ? ($selected->lesson->group->grade.$selected->lesson->group->name) : null,
            'date' => $date,
            'students' => $students,
            'statuses' => $statuses,
            'attendanceId' => $attendanceId,
            'lastSavedAt' => $session?->updated_at?->format('d/m/Y H:i'),
            'lessonNote' => isset($lessonNote) && $lessonNote ? [
                'id' => $lessonNote->id,
                'notes' => $lessonNote->notes,
                'savedAt' => $lessonNote->updated_at?->format('d/m/Y H:i'),
            ] : null,
            'lessonId' => $selected?->lesson_id,
            'assignments' => $assignments->map(fn ($a) => [
                'id' => $a->id,
                'type' => $a->type,
                'title' => $a->title,
                'scheduled_date' => $a->scheduled_date->toDateString(),
                'description' => $a->description,
            ]),
            'nextAssignmentDate' => $nextAssignmentDate ?? null,
            'schedulePattern' => $schedulePattern ?? [],
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

        $lesson = Lesson::findOrFail($validated['lesson_id']);
        $this->authorize('create', [Attendance::class, $lesson]);

        $date = Carbon::parse($validated['date'])->toDateString();

        $session = ClassSession::where('lesson_id', $validated['lesson_id'])
            ->whereDate('date', $date)
            ->first() ?? ClassSession::create(['lesson_id' => $validated['lesson_id'], 'date' => $date]);

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
        $this->authorize('delete', $attendance);

        $attendance->delete();

        return back();
    }
}
