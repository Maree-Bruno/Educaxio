<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ClassListController extends Controller
{
    public function index(Request $request)
    {
        $query = Group::with(['school', 'academicYear', 'lessons.subject', 'lessons.user'])
            ->withCount('students');

        if ($request->filled('school')) {
            $query->where('school_id', $request->integer('school'));
        }

        if ($request->filled('class')) {
            $query->where('slug', $request->string('class'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('grade', 'like', "%{$search}%")
                    ->orWhereHas('school', fn ($s) => $s->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('lessons', fn ($l) => $l->where('name', 'like', "%{$search}%"));
            });
        }

        return Inertia::render('ClassList', [
            'groups' => $query->get(),
            'schools' => School::orderBy('name')->get(['id', 'name']),
            'classes' => Group::orderBy('grade')->orderBy('name')->get(['slug', 'grade', 'name', 'school_id']),
            'filters' => $request->only(['school', 'class', 'search']),
        ]);
    }

    public function create(): void {}

    public function store(Request $request): void {}

    public function show(Group $group)
    {
        $group->load(['school', 'academicYear', 'lessons']);
        $students = $group->students()->paginate(10);
        $group->students_count = $students->total();

        return Inertia::render('ClassListShow', [
            'group' => $group,
            'students' => $students,
            'schools' => School::orderBy('name')->get(['id', 'name']),
            'academicYears' => AcademicYear::orderByDesc('year')->get(['id', 'year']),
            'subjects' => Subject::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Group $group): void {}

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'grade' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:10'],
            'school_id' => ['required', 'exists:schools,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
        ]);

        $school = School::find($validated['school_id']);

        $group->update([
            'grade' => $validated['grade'],
            'name' => $validated['name'],
            'slug' => Str::slug("{$school->slug}-{$validated['grade']}-{$validated['name']}"),
            'school_id' => $validated['school_id'],
            'academic_year_id' => $validated['academic_year_id'],
        ]);

        $subjectId = $validated['subject_id'] ?? null;
        $currentLesson = $group->lessons->first();

        if ($subjectId) {
            $subject = Subject::find($subjectId);
            if ($currentLesson) {
                $currentLesson->update([
                    'name' => $subject->name,
                    'subject_id' => $subjectId,
                    'academic_year_id' => $validated['academic_year_id'],
                ]);
            } else {
                $group->lessons()->create([
                    'name' => $subject->name,
                    'subject_id' => $subjectId,
                    'user_id' => auth()->id(),
                    'academic_year_id' => $validated['academic_year_id'],
                ]);
            }
        } else {
            $group->lessons()->delete();
        }

        return to_route('classlist.show', $group);
    }

    public function destroy(Group $group)
    {
        $group->delete();

        return to_route('classlist');
    }
}
