<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class StudentController extends Controller
{
    private function userSchoolIds(): Collection
    {
        return auth()->user()->schools()->pluck('schools.id');
    }

    public function index() {}

    public function store() {}

    public function show(Student $student)
    {
        $student->load([
            'groups:id,slug,grade,name,school_id',
            'groups.school:id,name',
            'school:id,name,slug',
        ]);

        $isAdmin = auth()->user()->schools()
            ->where('schools.id', $student->school_id)
            ->wherePivot('role', 'admin')
            ->exists();

        $absenceHistory = null;
        if ($isAdmin) {
            $absenceHistory = $student->attendanceStatuses()
                ->with([
                    'attendance.classsession.lesson.subject:id,name',
                    'attendance.classsession.lesson.group:id,grade,name',
                ])
                ->get()
                ->sortByDesc(fn ($s) => $s->attendance?->classsession?->date)
                ->values()
                ->map(fn ($s) => [
                    'date' => $s->attendance?->classsession?->date,
                    'type' => $s->type,
                    'subject' => $s->attendance?->classsession?->lesson?->subject?->name,
                    'group' => $s->attendance?->classsession?->lesson?->group
                        ? $s->attendance->classsession->lesson->group->grade.$s->attendance->classsession->lesson->group->name
                        : null,
                ]);
        }

        return Inertia::render('StudentShow', [
            'student' => $student,
            'isAdmin' => $isAdmin,
            'absenceHistory' => $absenceHistory,
        ]);
    }

    public function edit($id) {}

    public function update(Request $request, Student $student)
    {
        abort_unless($this->userSchoolIds()->contains($student->school_id), 403);

        $validated = $request->validate([
            'lastname' => ['required', 'string', 'max:100'],
            'firstname' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $student->update($validated);

        return back();
    }

    public function destroy($id) {}
}
