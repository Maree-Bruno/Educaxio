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

        $eagerLoads = [
            'attendance.classsession.lesson:id,group_id,subject_id,lm_level',
            'attendance.classsession.lesson.subject:id,name',
            'attendance.classsession.lesson.group:id,grade,name',
            'attendance.classsession.lesson.scheduleEntries.scheduleSlot:id,label,position',
            'attendance.classsession.lesson.users:id,name',
        ];

        $mapRecord = fn ($s) => [
            'date'    => $s->attendance?->classsession?->date,
            'type'    => $s->type,
            'subject' => $s->attendance?->classsession?->lesson?->subjectLabel(),
            'group'   => ($g = $s->attendance?->classsession?->lesson?->group)
                             ? $g->grade.$g->name : null,
            'time'    => ($session = $s->attendance?->classsession) && $session->date
                             ? $session->lesson?->scheduleEntries
                                   ->firstWhere('day_of_week', Carbon::parse($session->date)->dayOfWeekIso)
                                   ?->scheduleSlot?->label
                             : null,
            'teacher' => $s->attendance?->classsession?->lesson?->users?->first()?->name,
        ];

        $absenceHistory = null;

        if ($isAdmin) {
            $absenceHistory = $student->attendanceStatuses()
                ->with($eagerLoads)
                ->get()
                ->sortByDesc(fn ($s) => $s->attendance?->classsession?->date)
                ->values()
                ->map($mapRecord);
        } else {
            $teacherLessonIds = auth()->user()
                ->lessons()
                ->whereIn('group_id', $student->groups->pluck('id'))
                ->pluck('lessons.id');

            if ($teacherLessonIds->isNotEmpty()) {
                $absenceHistory = $student->attendanceStatuses()
                    ->whereHas('attendance.classsession', fn ($q) =>
                        $q->whereIn('lesson_id', $teacherLessonIds)
                    )
                    ->with($eagerLoads)
                    ->get()
                    ->sortByDesc(fn ($s) => $s->attendance?->classsession?->date)
                    ->values()
                    ->map($mapRecord);
            }
        }

        return Inertia::render('StudentShow', [
            'student'        => $student,
            'isAdmin'        => $isAdmin,
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
