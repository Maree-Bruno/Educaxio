<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\ScheduleEntry;
use App\Models\ScheduleSlot;
use App\Models\SchoolJoinRequest;
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
            $studentsCount = $school->students()->count();

            $teachersCount = $school->users()
                ->wherePivot('role', 'teacher')
                ->count();

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
            ->with([
                'lesson:id,group_id,subject_id',
                'lesson.group:id,grade,name,slug,school_id',
                'lesson.group.school:id,name,slug',
                'lesson.subject:id,name',
            ])
            ->get();

        $entriesBySlotId = $todayEntries->keyBy('schedule_slot_id');

        $slots = ScheduleSlot::orderBy('position')
            ->get(['id', 'position', 'label', 'type'])
            ->map(function ($s) use ($entriesBySlotId) {
                $entry = $entriesBySlotId->get($s->id);

                return [
                    'id' => $s->id,
                    'position' => $s->position,
                    'label' => $s->label,
                    'type' => $s->type,
                    'entry' => $entry ? [
                        'id' => $entry->id,
                        'subject' => $entry->lesson->subject->name,
                        'group' => $entry->lesson->group->grade.$entry->lesson->group->name,
                        'groupSlug' => $entry->lesson->group->slug,
                        'school' => $entry->lesson->group->school->name,
                        'room' => $entry->classroom,
                    ] : null,
                ];
            });

        $lessons = $user->lessons()
            ->with([
                'group:id,grade,name,slug,school_id',
                'group.school:id,name',
                'subject:id,name',
            ])
            ->get()
            ->keyBy('id');

        $lessonIds = $lessons->keys()->toArray();

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
