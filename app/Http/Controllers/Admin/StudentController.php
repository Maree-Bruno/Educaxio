<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index(Request $request, School $school)
    {
        $groups = $school->groups()
            ->orderBy('grade')
            ->orderBy('name')
            ->get(['id', 'grade', 'name']);

        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';
        $sort = in_array($request->string('sort')->toString(), ['lastname', 'firstname'])
            ? $request->string('sort')->toString()
            : 'lastname';

        $query = Student::where('school_id', $school->id)
            ->with('groups:id,grade,name,slug')
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

        return Inertia::render('admin/Students', [
            'school' => $school->only('id', 'name', 'slug'),
            'students' => $query->paginate(30)->withQueryString(),
            'groups' => $groups,
            'filters' => (object) $request->only(['group', 'sort', 'dir', 'search']),
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

        return back();
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

        return back();
    }

    public function destroy(School $school, Student $student)
    {
        abort_unless($student->school_id === $school->id, 403);

        $student->delete();

        return back();
    }
}
