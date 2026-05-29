<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\SchoolJoinRequest;
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
                    'id'       => $r->id,
                    'user'     => ['id' => $r->user->id, 'name' => $r->user->name, 'email' => $r->user->email],
                    'subjects' => $r->user->subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
                ]);

            $recentStudents = $school->students()
                ->orderByDesc('created_at')
                ->limit(5)
                ->with('groups:id,grade,name,slug')
                ->get(['id', 'firstname', 'lastname'])
                ->map(fn ($s) => [
                    'id'        => $s->id,
                    'firstname' => $s->firstname,
                    'lastname'  => $s->lastname,
                    'groups'    => $s->groups->map(fn ($g) => ['id' => $g->id, 'grade' => $g->grade, 'name' => $g->name, 'slug' => $g->slug]),
                ]);

            $teachers = $school->users()
                ->wherePivot('role', 'teacher')
                ->orderBy('users.name')
                ->limit(5)
                ->with('subjects:id,name')
                ->get(['users.id', 'users.name', 'users.email'])
                ->map(fn ($t) => [
                    'id'       => $t->id,
                    'name'     => $t->name,
                    'email'    => $t->email,
                    'subjects' => $t->subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
                ]);

            return [
                'id'             => $school->id,
                'name'           => $school->name,
                'slug'           => $school->slug,
                'stats'          => [
                    'students' => $studentsCount,
                    'teachers' => $teachersCount,
                    'pending'  => $pendingCount,
                    'lessons'  => $lessonsCount,
                ],
                'joinRequests'   => $joinRequests,
                'recentStudents' => $recentStudents,
                'teachers'       => $teachers,
            ];
        });

        return Inertia::render('Dashboard', [
            'schools' => $schools,
        ]);
    }
}