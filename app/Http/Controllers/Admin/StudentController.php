<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\DetectsCurrentAcademicYear;
use App\Http\Controllers\Concerns\HandlesSorting;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    use DetectsCurrentAcademicYear;
    use HandlesSorting;

    public function index(Request $request, School $school)
    {
        $schoolIds = collect([$school->id]);
        $currentYearId = $this->currentAcademicYearId($schoolIds);
        $selectedYearId = $this->selectedAcademicYearId($currentYearId, $request);

        $groups = $school->groups()
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->orderBy('grade')
            ->orderBy('name')
            ->get(['id', 'grade', 'name', 'slug']);

        $academicYears = $this->academicYearsForSchools(collect([$school->id]), $currentYearId);

        $dir = $this->sortDir($request);
        $sort = $this->sortCol($request, ['lastname', 'firstname'], 'lastname');

        $query = Student::where('school_id', $school->id)
            ->with(['groups' => fn ($q) => $q
                ->when($selectedYearId, fn ($g) => $g->where('academic_year_id', $selectedYearId))
                ->select('groups.id', 'grade', 'name', 'slug'),
            ])
            ->when($selectedYearId, fn ($q) => $q->whereHas('groups', fn ($g) =>
                $g->where('academic_year_id', $selectedYearId)
            ))
            ->orderBy($sort, $dir)
            ->orderBy($sort === 'lastname' ? 'firstname' : 'lastname');

        if ($request->filled('group')) {
            $query->whereHas('groups', fn ($q) => $q->where('groups.id', $request->integer('group')));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where(fn ($q) => $q
                ->where('lastname', 'like', "%{$search}%")
                ->orWhere('firstname', 'like', "%{$search}%"),
            );
        }

        $students = $query->paginate(30)->withQueryString();
        $filters = (object) array_merge(
            $request->only(['group', 'sort', 'dir', 'search']),
            ['year' => $selectedYearId ? (string) $selectedYearId : null],
        );

        return Inertia::render('admin/Students', [
            'school'        => $school->only('id', 'name', 'slug'),
            'students'      => $students,
            'groups'        => $groups,
            'academicYears' => $academicYears,
            'filters'       => $filters,
        ]);
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'lastname' => ['required', 'string', 'max:100'],
            'firstname' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:groups,id'],
        ]);

        $schoolGroupIds = $school->groups()->pluck('groups.id');
        $groupIds = collect($validated['group_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $schoolGroupIds->contains($id));

        $student = Student::create([
            'lastname' => $validated['lastname'],
            'firstname' => $validated['firstname'],
            'email' => $validated['email'] ?? null,
            'school_id' => $school->id,
        ]);

        if ($groupIds->isNotEmpty()) {
            $student->groups()->attach($groupIds->all());
        }

        return to_route('admin.students.index', $school);
    }

    public function update(Request $request, School $school, Student $student)
    {
        abort_unless($student->school_id === $school->id, 403);

        $validated = $request->validate([
            'lastname' => ['required', 'string', 'max:100'],
            'firstname' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:groups,id'],
        ]);

        $student->update([
            'lastname' => $validated['lastname'],
            'firstname' => $validated['firstname'],
            'email' => $validated['email'] ?? null,
        ]);

        $schoolGroupIds = $school->groups()->pluck('groups.id');
        $groupIds = collect($validated['group_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $schoolGroupIds->contains($id));

        $student->groups()->sync($groupIds->all());

        return to_route('admin.students.index', $school);
    }

    public function destroy(School $school, Student $student)
    {
        abort_unless($student->school_id === $school->id, 403);

        $student->delete();

        return to_route('admin.students.index', $school);
    }
}
