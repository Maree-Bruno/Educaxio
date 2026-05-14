<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ClassListController extends Controller
{
    private function userSchoolIds(): Collection
    {
        return auth()->user()->schools()->pluck('schools.id');
    }

    public function index(Request $request)
    {
        $schoolIds = $this->userSchoolIds();

        $query = Group::with([
            'school:id,name',
            'academicYear:id,year',
            'lessons:id,name,group_id',
        ])
            ->withCount('students')
            ->whereIn('school_id', $schoolIds);

        if ($request->filled('school')) {
            $query->where('school_id', $request->integer('school'));
        }

        if ($request->filled('class')) {
            $query->where('slug', $request->string('class'));
        }

        if ($request->filled('year')) {
            $query->where('academic_year_id', $request->integer('year'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();

            if (preg_match('/^(\d+)\s*([a-zA-Z]*)$/', $search, $matches)) {
                $query->where('grade', 'like', "%{$matches[1]}%");
                if ($matches[2] !== '') {
                    $query->where('name', 'like', "%{$matches[2]}%");
                }
            } else {
                $query->where('name', 'like', "%{$search}%");
            }
        }

        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';

        match ($request->string('sort')->toString()) {
            'school' => $query->orderBy(School::select('name')->whereColumn('schools.id', 'groups.school_id'), $dir),
            'students' => $query->orderBy('students_count', $dir),
            'subject' => $query->orderBy(Lesson::select('name')->whereColumn('lessons.group_id', 'groups.id')->limit(1), $dir),
            default => $query->orderBy('grade', $dir)->orderBy('name', $dir),
        };

        return Inertia::render('ClassList', [
            'groups' => $query->get(),
            'schools' => auth()->user()->schools()->orderBy('name')->get(['schools.id', 'schools.name']),
            'academicYears' => AcademicYear::whereHas('schools', fn ($q) => $q->whereIn('schools.id', $schoolIds))
                ->orderByDesc('year')->get(['id', 'year']),
            'classes' => Group::whereIn('school_id', $schoolIds)
                ->orderBy('grade')->orderBy('name')->get(['slug', 'grade', 'name', 'school_id']),
            'filters' => $request->only(['school', 'class', 'year', 'search', 'sort', 'dir']),
        ]);
    }

    public function create()
    {
        $schoolIds = $this->userSchoolIds();

        return Inertia::render('ClassListCreate', [
            'schools' => auth()->user()->schools()->orderBy('name')->get(['schools.id', 'schools.name']),
            'academicYears' => AcademicYear::whereHas('schools', fn ($q) => $q->whereIn('schools.id', $schoolIds))
                ->orderByDesc('year')->get(['id', 'year']),
            'subjects' => Subject::whereHas('schools', fn ($q) => $q->whereIn('schools.id', $schoolIds))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:10'],
            'school_id' => ['required', 'exists:schools,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
        ]);

        $school = School::findOrFail($validated['school_id']);

        $group = Group::create([
            'grade' => $validated['grade'],
            'name' => $validated['name'],
            'slug' => Str::slug("{$school->slug}-{$validated['grade']}-{$validated['name']}"),
            'school_id' => $validated['school_id'],
            'academic_year_id' => $validated['academic_year_id'],
        ]);

        if ($validated['subject_id'] ?? null) {
            $subject = Subject::findOrFail($validated['subject_id']);
            $group->lessons()->create([
                'name' => $subject->name,
                'subject_id' => $subject->id,
            ]);
        }

        return to_route('classlist.show', $group);
    }

    public function show(Group $group, Request $request)
    {
        $schoolIds = $this->userSchoolIds();

        $group->load(['school', 'academicYear', 'lessons']);
        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';
        $sortCol = in_array($request->string('sort')->toString(), ['lastname', 'firstname'])
            ? $request->string('sort')->toString()
            : 'lastname';
        $students = $group->students()->orderBy($sortCol, $dir)->paginate(10);
        $group->students_count = $students->total();

        return Inertia::render('ClassListShow', [
            'group' => $group,
            'students' => $students,
            'schools' => auth()->user()->schools()->orderBy('name')->get(['schools.id', 'schools.name']),
            'academicYears' => AcademicYear::whereHas('schools', fn ($q) => $q->whereIn('schools.id', $schoolIds))
                ->orderByDesc('year')->get(['id', 'year']),
            'subjects' => Subject::whereHas('schools', fn ($q) => $q->whereIn('schools.id', $schoolIds))
                ->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['sort', 'dir']),
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

        $school = School::findOrFail($validated['school_id']);

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
            $subject = Subject::findOrFail($subjectId);
            if ($currentLesson) {
                $currentLesson->update([
                    'name' => $subject->name,
                    'subject_id' => $subjectId,
                ]);
            } else {
                $group->lessons()->create([
                    'name' => $subject->name,
                    'subject_id' => $subjectId,
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