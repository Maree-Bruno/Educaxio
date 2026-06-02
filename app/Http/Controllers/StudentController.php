<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index() {}

    public function store() {}

    public function show(Student $student)
    {
        $student->load([
            'groups:id,slug,grade,name,school_id',
            'groups.school:id,name',
            'school:id,name,slug',
        ]);

        $this->authorize('view', $student);

        $isAdmin = auth()->user()->schools()
            ->where('schools.id', $student->school_id)
            ->wherePivot('role', 'admin')
            ->exists();

        $absenceHistory = null;
        if ($isAdmin) {
            $absenceHistory = $student->attendanceStatuses()
                ->with([
                    'attendance.classsession.lesson:id,group_id,subject_id,lm_level',
                    'attendance.classsession.lesson.subject:id,name',
                    'attendance.classsession.lesson.group:id,grade,name',
                    'attendance.classsession.lesson.scheduleEntries.scheduleSlot:id,label,position',
                ])
                ->get()
                ->sortByDesc(fn ($s) => $s->attendance?->classsession?->date)
                ->values()
                ->map(fn ($s) => [
                    'date' => $s->attendance?->classsession?->date,
                    'type' => $s->type,
                    'subject' => $s->attendance?->classsession?->lesson?->subjectLabel(),
                    'group' => $s->attendance?->classsession?->lesson?->group
                        ? $s->attendance->classsession->lesson->group->grade.$s->attendance->classsession->lesson->group->name
                        : null,
                    'time' => ($session = $s->attendance?->classsession) && $session->date
                        ? $session->lesson?->scheduleEntries
                            ->firstWhere('day_of_week', Carbon::parse($session->date)->dayOfWeekIso)
                            ?->scheduleSlot?->label
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
        $this->authorize('update', $student);

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
