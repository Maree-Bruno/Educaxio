<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\DetectsCurrentAcademicYear;
use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\ScheduleEntry;
use App\Models\ScheduleSlot;
use App\Models\SchoolJoinRequest;
use App\Models\SchoolSlotTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use DetectsCurrentAcademicYear;
    public function __invoke(Request $request)
    {
        $user = auth()->user();

        $adminSchools = $user->schools()
            ->wherePivot('role', 'admin')
            ->get(['schools.id', 'schools.name', 'schools.slug']);

        if ($adminSchools->isEmpty()) {
            return $this->teacherDashboard($user);
        }

        $schools = $adminSchools->map(function ($school) {
            $pendingCount = SchoolJoinRequest::where('school_id', $school->id)
                ->where('status', 'pending')
                ->count();

            $currentYearId = $this->currentAcademicYearId(collect([$school->id]));

            $lessonsCount = Lesson::whereHas('group', fn ($q) => $q->where('school_id', $school->id))->count();

            $joinRequests = $this->pendingJoinRequests($school->id, 5);

            $studentsPaginator = $school->students()
                ->orderByDesc('created_at')
                ->with(['groups' => fn ($q) => $q
                    ->when($currentYearId, fn ($g) => $g->where('academic_year_id', $currentYearId))
                    ->select('groups.id', 'groups.grade', 'groups.name', 'groups.slug'),
                ])
                ->paginate(5, ['id', 'firstname', 'lastname'], 'students_page')
                ->onEachSide(1);

            $studentsCount = $studentsPaginator->total();

            $recentStudents = [
                'data' => $studentsPaginator->getCollection()->map(fn ($s) => [
                    'id' => $s->id,
                    'firstname' => $s->firstname,
                    'lastname' => $s->lastname,
                    'groups' => $s->groups->map(fn ($g) => ['id' => $g->id, 'grade' => $g->grade, 'name' => $g->name, 'slug' => $g->slug]),
                ]),
                'current_page' => $studentsPaginator->currentPage(),
                'last_page' => $studentsPaginator->lastPage(),
                'links' => $studentsPaginator->toArray()['links'],
            ];

            $teachersPaginator = $school->users()
                ->wherePivot('role', 'teacher')
                ->orderBy('users.name')
                ->with('subjects:id,name')
                ->paginate(5, ['users.id', 'users.name', 'users.email'], 'teachers_page')
                ->onEachSide(1);

            $teachersCount = $teachersPaginator->total();

            $teachers = [
                'data' => $teachersPaginator->getCollection()->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'email' => $t->email,
                    'subjects' => $t->subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
                ]),
                'current_page' => $teachersPaginator->currentPage(),
                'last_page' => $teachersPaginator->lastPage(),
                'links' => $teachersPaginator->toArray()['links'],
            ];

            return [
                'id' => $school->id,
                'name' => $school->name,
                'slug' => $school->slug,
                'stats' => [
                    'students' => $studentsCount,
                    'teachers' => $teachersCount,
                    'pending'  => $pendingCount,
                    'lessons'  => $lessonsCount,
                ],
                'joinRequests' => $joinRequests,
                'recentStudents' => $recentStudents,
                'teachers' => $teachers,
            ];
        });

        return Inertia::render('Dashboard', [
            'schools' => $schools,
        ]);
    }

    private function teacherDashboard($user)
    {
        $request = request();
        $today = $request->filled('date') ? Carbon::parse($request->date) : now();
        $dow = $today->dayOfWeekIso;

        $schoolIds = $user->schools()->pluck('schools.id');
        $currentYearId = $this->currentAcademicYearId($schoolIds);

        $todayEntries = ScheduleEntry::where('schedule_entries.day_of_week', $dow)
            ->whereHas('lesson.users', fn ($q) => $q->where('users.id', $user->id))
            ->when($currentYearId, fn ($q) => $q->whereHas('schedule', fn ($s) =>
                $s->where('academic_year_id', $currentYearId)
            ))
            ->join('schedule_slots', 'schedule_entries.schedule_slot_id', '=', 'schedule_slots.id')
            ->orderBy('schedule_slots.position')
            ->select('schedule_entries.*')
            ->get();

        $lessons = $user->lessons()
            ->with([
                'group:id,grade,name,slug,school_id',
                'group.school:id,name',
                'subject:id,name',
                'scheduleEntries.scheduleSlot',
            ])
            ->when(
                $currentYearId,
                fn ($q) => $q->whereHas('group', fn ($g) => $g->where('academic_year_id', $currentYearId)),
                fn ($q) => $q->whereHas('group', fn ($g) => $g->whereHas('academicYear.schools', fn ($s) =>
                    $s->whereIn('schools.id', $schoolIds)->whereNull('academic_year_school.archived_at')
                )),
            )
            ->get()
            ->keyBy('id');

        $lessonIds = $lessons->keys()->toArray();

        $entriesBySlotId = $todayEntries->keyBy('schedule_slot_id');

        $schoolSlotTimes = $this->schoolSlotTimes($schoolIds);

        $slots = ScheduleSlot::orderBy('position')
            ->get(['id', 'position', 'label', 'type', 'start_time', 'end_time'])
            ->map(function ($s) use ($entriesBySlotId, $lessons, $schoolSlotTimes, $schoolIds) {
                $entry = $entriesBySlotId->get($s->id);
                $lesson = $entry ? $lessons->get($entry->lesson_id) : null;

                $schoolId = $lesson ? $lesson->group->school_id : $schoolIds->first();
                $slotTimes = $schoolSlotTimes[$schoolId] ?? collect();
                $override = $slotTimes[$s->id] ?? null;

                $globalStart = $s->start_time ? substr($s->start_time, 0, 5) : null;
                $globalEnd = $s->end_time ? substr($s->end_time, 0, 5) : null;

                return [
                    'id' => $s->id,
                    'position' => $s->position,
                    'label' => $s->label,
                    'type' => $s->type,
                    'start_time' => $override ? $override['start_time'] : $globalStart,
                    'end_time' => $override ? $override['end_time'] : $globalEnd,
                    'entry' => ($entry && $lesson) ? [
                        'creneau'   => Str::slug($lesson->group->slug.'-'.$s->label),
                        'subject'   => $lesson->subjectLabel(),
                        'group'     => $lesson->group->grade.$lesson->group->name,
                        'groupSlug' => $lesson->group->slug,
                        'school'    => $lesson->group->school->name,
                        'room'      => $entry->classroom,
                    ] : null,
                ];
            });

        $groups = $lessons
            ->groupBy('group_id')
            ->map(fn ($ls) => [
                'id' => $ls->first()->group->id,
                'grade' => $ls->first()->group->grade,
                'name' => $ls->first()->group->name,
                'slug' => $ls->first()->group->slug,
                'school' => $ls->first()->group->school->name,
                'subjects' => $ls->map(fn ($l) => $l->subjectLabel())->unique()->values(),
            ])
            ->values();

        $assignmentQuery = Assignment::whereIn('lesson_id', $lessonIds)
            ->where('scheduled_date', '>=', now()->toDateString());

        $upcomingAssignmentsTotal = $assignmentQuery->count();

        $upcomingAssignments = $assignmentQuery
            ->orderBy('scheduled_date')
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'id'             => $a->id,
                'type'           => $a->type,
                'title'          => $a->title,
                'scheduled_date' => $a->scheduled_date->toDateString(),
                'description'    => $a->description,
                'group'          => $lessons[$a->lesson_id]->group->grade.$lessons[$a->lesson_id]->group->name,
                'group_slug'     => $lessons[$a->lesson_id]->group->slug,
                'subject'        => $lessons[$a->lesson_id]->subjectLabel(),
                'school'         => $lessons[$a->lesson_id]->group->school->name,
                'slot_label'     => $a->slot_label ?? $lessons[$a->lesson_id]->scheduleEntries
                    ->first(fn ($e) => $e->day_of_week === \Carbon\Carbon::parse($a->scheduled_date)->dayOfWeekIso)
                    ?->scheduleSlot?->label,
            ]);

        return Inertia::render('TeacherDashboard', [
            'singleSchool' => $schoolIds->count() === 1,
            'slots' => $slots,
            'groups' => $groups,
            'date' => $today->locale('fr')->isoFormat('dddd D MMMM YYYY'),
            'selectedDate' => $today->toDateString(),
            'upcomingAssignments' => $upcomingAssignments,
            'upcomingAssignmentsTotal' => $upcomingAssignmentsTotal,
            'user' => $user,
        ]);
    }
}
