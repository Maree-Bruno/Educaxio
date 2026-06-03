<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\DetectsCurrentAcademicYear;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LessonController extends Controller
{
    use DetectsCurrentAcademicYear;

    public function index(School $school)
    {
        $currentYearId = $this->currentAcademicYearId(collect([$school->id]));

        $lessons = Lesson::whereHas('group', fn ($q) => $q
                ->where('school_id', $school->id)
                ->when($currentYearId, fn ($g) => $g->where('academic_year_id', $currentYearId)),
            )
            ->with([
                'group:id,grade,name',
                'subject:id,name,is_language',
                'users:id,name',
            ])
            ->get(['id', 'group_id', 'subject_id', 'lm_level']);

        $groups = $school->groups()
            ->when($currentYearId, fn ($q) => $q->where('academic_year_id', $currentYearId))
            ->orderBy('grade')
            ->orderBy('name')
            ->get(['id', 'grade', 'name', 'slug']);

        $subjects = $school->subjects()
            ->orderBy('name')
            ->get(['subjects.id', 'subjects.name', 'subjects.is_language']);

        $teachers = $school->users()
            ->wherePivot('role', 'teacher')
            ->with('subjects:id')
            ->orderBy('name')
            ->get(['users.id', 'users.name'])
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'subject_ids' => $t->subjects->pluck('id')->values()->all(),
            ]);

        return Inertia::render('admin/Lessons', [
            'school' => $school->only('id', 'name', 'slug'),
            'lessons' => $lessons,
            'groups' => $groups,
            'subjects' => $subjects,
            'teachers' => $teachers,
        ]);
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'group_id' => ['required', 'exists:groups,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'lm_level' => ['nullable', 'integer', Rule::in([1, 2, 3])],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['exists:users,id'],
        ]);

        abort_unless(
            $school->groups()->where('id', $validated['group_id'])->exists(),
            403,
        );

        $lesson = Lesson::firstOrCreate(
            ['group_id' => $validated['group_id'], 'subject_id' => $validated['subject_id']],
            ['lm_level' => $validated['lm_level'] ?? null],
        );

        if (! empty($validated['teacher_ids'])) {
            $lesson->users()->syncWithoutDetaching($validated['teacher_ids']);
        }

        return back();
    }

    public function update(Request $request, School $school, Lesson $lesson)
    {
        abort_unless($lesson->group->school_id === $school->id, 403);

        $validated = $request->validate([
            'lm_level' => ['nullable', 'integer', Rule::in([1, 2, 3])],
        ]);

        $lesson->update(['lm_level' => $validated['lm_level']]);

        return back();
    }

    public function syncTeachers(Request $request, School $school, Lesson $lesson)
    {
        abort_unless(
            $lesson->group->school_id === $school->id,
            403,
        );

        $validated = $request->validate([
            'teacher_ids' => ['present', 'array'],
            'teacher_ids.*' => ['exists:users,id'],
        ]);

        // Only allow teachers that belong to this school
        $schoolTeacherIds = $school->users()
            ->wherePivot('role', 'teacher')
            ->pluck('users.id');

        $safeIds = collect($validated['teacher_ids'])->intersect($schoolTeacherIds);

        $lesson->users()->sync($safeIds);

        return back();
    }

    public function destroy(School $school, Lesson $lesson)
    {
        abort_unless($lesson->group->school_id === $school->id, 403);

        $lesson->delete();

        return back();
    }
}
