<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\ScheduleEntry;
use App\Models\ScheduleSlot;
use App\Models\SchoolJoinRequest;
use App\Models\SchoolSlotTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
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

            $lessonsCount = Lesson::whereHas('group', fn ($q) => $q->where('school_id', $school->id))->count();

            $joinRequests = SchoolJoinRequest::where('school_id', $school->id)
                ->where('status', 'pending')
                ->with(['user:id,name,email', 'user.subjects:id,name'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'user' => ['id' => $r->user->id, 'name' => $r->user->name, 'email' => $r->user->email],
                    'subjects' => $r->user->subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
                ]);

            $studentsPaginator = $school->students()
                ->orderByDesc('created_at')
                ->with('groups:id,grade,name,slug')
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
                    'pending' => $pendingCount,
                    'lessons' => $lessonsCount,
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
        $todayEntries = ScheduleEntry::where('schedule_entries.day_of_week', $dow)
            ->whereHas('lesson.users', fn ($q) => $q->where('users.id', $user->id))
            ->join('schedule_slots', 'schedule_entries.schedule_slot_id', '=', 'schedule_slots.id')
            ->orderBy('schedule_slots.position')
            ->select('schedule_entries.*')
            ->get();

        $lessons = $user->lessons()
            ->with([
                'group:id,grade,name,slug,school_id',
                'group.school:id,name',
                'subject:id,name',
            ])
            ->get()
            ->keyBy('id');

        $lessonIds = $lessons->keys()->toArray();

        $entriesBySlotId = $todayEntries->keyBy('schedule_slot_id');

        $userSchoolIds = $user->schools()->pluck('schools.id');
        $schoolSlotTimes = SchoolSlotTime::whereIn('school_id', $userSchoolIds)
            ->get()
            ->groupBy('school_id')
            ->map(fn ($rows) => $rows->keyBy('schedule_slot_id')->map(fn ($r) => [
                'start_time' => substr($r->start_time, 0, 5),
                'end_time'   => substr($r->end_time, 0, 5),
            ]));

        $indicatorSchoolId = $schoolSlotTimes->keys()->first() ?? $userSchoolIds->first();
        $slotTimesForIndicator = $schoolSlotTimes[$indicatorSchoolId] ?? collect();

        $slots = ScheduleSlot::orderBy('position')
            ->get(['id', 'position', 'label', 'type', 'start_time', 'end_time'])
            ->map(function ($s) use ($entriesBySlotId, $lessons, $slotTimesForIndicator) {
                $override = $slotTimesForIndicator[$s->id] ?? null;
                $globalStart = $s->start_time ? substr($s->start_time, 0, 5) : null;
                $globalEnd   = $s->end_time   ? substr($s->end_time, 0, 5)   : null;
                $entry = $entriesBySlotId->get($s->id);
                $lesson = $entry ? $lessons->get($entry->lesson_id) : null;

                return [
                    'id'         => $s->id,
                    'position'   => $s->position,
                    'label'      => $s->label,
                    'type'       => $s->type,
                    'start_time' => $override ? $override['start_time'] : $globalStart,
                    'end_time'   => $override ? $override['end_time']   : $globalEnd,
                    'entry' => ($entry && $lesson) ? [
                        'id' => $entry->id,
                        'subject' => $lesson->subject->name,
                        'group' => $lesson->group->grade.$lesson->group->name,
                        'groupSlug' => $lesson->group->slug,
                        'school' => $lesson->group->school->name,
                        'room' => $entry->classroom,
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
                'subjects' => $ls->map(fn ($l) => $l->subject->name)->unique()->values(),
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
                'id' => $a->id,
                'type' => $a->type,
                'title' => $a->title,
                'scheduled_date' => $a->scheduled_date->toDateString(),
                'description' => $a->description,
                'group' => $lessons[$a->lesson_id]->group->grade.$lessons[$a->lesson_id]->group->name,
                'subject' => $lessons[$a->lesson_id]->subject->name,
                'school' => $lessons[$a->lesson_id]->group->school->name,
            ]);

        return Inertia::render('TeacherDashboard', [
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
