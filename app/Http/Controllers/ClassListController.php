<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ClassListController extends Controller
{
    private function scopedGroupQuery(Collection $adminSchoolIds, Collection $teacherSchoolIds): Builder
    {
        if ($adminSchoolIds->isEmpty() && $teacherSchoolIds->isEmpty()) {
            return Group::query()->whereRaw('1 = 0');
        }

        $userId = auth()->id();

        return Group::query()->where(function ($q) use ($adminSchoolIds, $teacherSchoolIds, $userId) {
            if ($adminSchoolIds->isNotEmpty()) {
                $q->whereIn('school_id', $adminSchoolIds);
            }

            if ($teacherSchoolIds->isNotEmpty()) {
                $q->orWhere(function ($sub) use ($teacherSchoolIds, $userId) {
                    $sub->whereIn('school_id', $teacherSchoolIds)
                        ->whereHas('lessons.users', fn ($u) => $u->where('users.id', $userId));
                });
            }
        });
    }

    public function index(Request $request)
    {
        $userSchools = auth()->user()->schools()->orderBy('name')->get(['schools.id', 'schools.name', 'schools.slug']);
        $adminSchoolIds = $userSchools->filter(fn ($s) => $s->pivot->role === 'admin')->pluck('id');
        $teacherSchoolIds = $userSchools->filter(fn ($s) => $s->pivot->role === 'teacher')->pluck('id');
        $schoolIds = $userSchools->pluck('id');

        $userId = auth()->id();
        $lessonsLoad = $teacherSchoolIds->isNotEmpty()
            ? ['lessons' => fn ($q) => $q->whereHas('users', fn ($u) => $u->where('users.id', $userId))
                ->select('lessons.id', 'lessons.group_id', 'lessons.subject_id', 'lessons.lm_level')
                ->with('subject:id,name')]
            : ['lessons:id,group_id,subject_id,lm_level', 'lessons.subject:id,name'];

        $query = $this->scopedGroupQuery($adminSchoolIds, $teacherSchoolIds)->with([
            'school:id,name',
            'academicYear:id,year',
            ...$lessonsLoad,
        ])->withCount('students');

        if ($request->filled('school')) {
            $school = $userSchools->firstWhere('slug', $request->string('school')->toString());
            if ($school) {
                $query->where('school_id', $school->id);
            }
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
            'subject' => $query->orderBy(
                Lesson::select('subjects.name')
                    ->join('subjects', 'subjects.id', '=', 'lessons.subject_id')
                    ->whereColumn('lessons.group_id', 'groups.id')
                    ->limit(1),
                $dir
            ),
            default => $query->orderBy('grade', $dir)->orderBy('name', $dir),
        };

        return Inertia::render('ClassList', [
            'groups' => $query->get(),
            'schools' => $userSchools,
            'academicYears' => AcademicYear::whereHas('schools', fn ($q) => $q->whereIn('schools.id', $schoolIds))
                ->orderByDesc('year')->get(['id', 'year']),
            'classes' => $this->scopedGroupQuery($adminSchoolIds, $teacherSchoolIds)
                ->orderBy('grade')->orderBy('name')->get(['slug', 'grade', 'name', 'school_id']),
            'filters' => (object) $request->only(['school', 'class', 'year', 'search', 'sort', 'dir']),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Group::class);

        $adminSchools = auth()->user()->schools()->wherePivot('role', 'admin')->orderBy('name')->get(['schools.id', 'schools.name', 'schools.slug']);
        $adminSchoolIds = $adminSchools->pluck('id');
        $academicYears = AcademicYear::whereHas('schools', fn ($q) => $q->whereIn('schools.id', $adminSchoolIds))
            ->orderByDesc('year')->get(['id', 'year']);

        $defaultSchoolId = null;
        $schoolSlug = $request->string('school')->toString();
        if ($request->filled('school')) {
            $defaultSchoolId = $adminSchools->firstWhere('slug', $schoolSlug)?->id;
        }
        if (! $defaultSchoolId && $adminSchools->count() === 1) {
            $defaultSchoolId = $adminSchools->first()->id;
            $schoolSlug = $adminSchools->first()->slug;
        }

        $breadcrumb = match ($request->string('from')->toString()) {
            'lessons' => [
                ['label' => 'Attribution des cours', 'href' => "/schools/{$schoolSlug}/lessons"],
                ['label' => 'Nouvelle classe'],
            ],
            default => [
                ['label' => 'Liste de classe', 'href' => '/classlist'],
                ['label' => 'Nouvelle classe'],
            ],
        };

        return Inertia::render('ClassListCreate', [
            'academicYears' => $academicYears,
            'subjects' => Subject::whereHas('schools', fn ($q) => $q->whereIn('schools.id', $adminSchoolIds))
                ->orderBy('name')->get(['id', 'name']),
            'defaults' => [
                'school_id' => $defaultSchoolId,
                'academic_year_id' => $academicYears->first()?->id,
            ],
            'breadcrumb' => $breadcrumb,
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
        $this->authorize('update', new Group(['school_id' => $school->id]));

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
                'subject_id' => $subject->id,
            ]);
        }

        return to_route('classlist.show', $group);
    }

    public function show(Group $group, Request $request)
    {
        $userSchools = auth()->user()->loadMissing('schools')->schools;
        $schoolIds = $userSchools->pluck('id');
        $userId = auth()->id();

        $canManage = $userSchools->contains(fn ($s) => $s->id === $group->school_id && $s->pivot->role === 'admin');
        $isTeacher = ! $canManage && $userSchools->contains(fn ($s) => $s->pivot->role === 'teacher');
        $lessonsLoad = $isTeacher
            ? ['lessons' => fn ($q) => $q->whereHas('users', fn ($u) => $u->where('users.id', $userId))]
            : ['lessons'];
        $group->load(['school', 'academicYear', ...$lessonsLoad]);

        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';
        $sortCol = in_array($request->string('sort')->toString(), ['lastname', 'firstname'])
            ? $request->string('sort')->toString()
            : 'lastname';
        $search = $request->string('search')->trim()->toString();

        $students = $group->students()
            ->when($search, fn ($q) => $q->where(fn ($inner) => $inner
                ->where('lastname', 'like', "%{$search}%")
                ->orWhere('firstname', 'like', "%{$search}%")
            ))
            ->orderBy($sortCol, $dir)
            ->orderBy($sortCol === 'lastname' ? 'firstname' : 'lastname')
            ->paginate(10);
        $group->students_count = $students->total();

        $schoolStudents = $canManage
            ? Student::where('school_id', $group->school_id)
                ->whereNotIn('id', $group->students()->pluck('students.id'))
                ->with('groups:id,grade,name')
                ->orderBy('lastname')
                ->get(['id', 'lastname', 'firstname'])
            : [];

        return Inertia::render('ClassListShow', [
            'group' => $group,
            'students' => $students,
            'canManage' => $canManage,
            'isTeacher' => $isTeacher,
            'academicYears' => AcademicYear::whereHas('schools', fn ($q) => $q->whereIn('schools.id', $schoolIds))
                ->orderByDesc('year')->get(['id', 'year']),
            'subjects' => Subject::whereHas('schools', fn ($q) => $q->whereIn('schools.id', $schoolIds))
                ->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['sort', 'dir', 'search']),
            'schoolStudents' => $schoolStudents,
        ]);
    }

    public function attachStudent(Request $request, Group $group)
    {
        $this->authorize('attachStudent', $group);

        if ($request->filled('student_ids')) {
            $validated = $request->validate([
                'student_ids' => ['required', 'array'],
                'student_ids.*' => ['integer', 'exists:students,id'],
            ]);
            $ids = Student::whereIn('id', $validated['student_ids'])
                ->where('school_id', $group->school_id)
                ->pluck('id');
            $group->students()->syncWithoutDetaching($ids->all());
        } else {
            // Create new student and attach
            $validated = $request->validate([
                'lastname' => ['required', 'string', 'max:100'],
                'firstname' => ['required', 'string', 'max:100'],
                'email' => ['nullable', 'email', 'max:255'],
            ]);
            $student = Student::create([
                'lastname' => $validated['lastname'],
                'firstname' => $validated['firstname'],
                'email' => $validated['email'] ?? null,
                'school_id' => $group->school_id,
            ]);
            $group->students()->attach($student->id);
        }

        return back();
    }

    public function detachStudent(Group $group, Student $student)
    {
        $this->authorize('detachStudent', $group);
        $group->students()->detach($student->id);
        return back();
    }

    public function edit(Group $group): void {}

    public function update(Request $request, Group $group)
    {
        $this->authorize('update', $group);

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
                $currentLesson->update(['subject_id' => $subjectId]);
            } else {
                $group->lessons()->create(['subject_id' => $subjectId]);
            }
        } else {
            $group->lessons()->delete();
        }

        return to_route('classlist.show', $group);
    }

    public function destroy(Group $group)
    {
        $this->authorize('delete', $group);

        $group->delete();

        return to_route('classlist');
    }
}
