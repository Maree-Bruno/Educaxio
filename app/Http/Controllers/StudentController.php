<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index() {}

    public function create(Request $request)
    {
        return Inertia::render('StudentCreate', [
            'groups' => Group::with('school:id,name')
                ->orderBy('grade')
                ->orderBy('name')
                ->get(['id', 'slug', 'grade', 'name', 'school_id']),
            'preselectedGroup' => $request->integer('group') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lastname' => ['required', 'string', 'max:100'],
            'firstname' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'group_id' => ['nullable', 'exists:groups,id'],
        ]);

        $student = Student::create([
            'lastname' => $validated['lastname'],
            'firstname' => $validated['firstname'],
            'email' => $validated['email'] ?: null,
        ]);

        if ($validated['group_id']) {
            $student->groups()->attach($validated['group_id']);
            $group = Group::find($validated['group_id']);

            return to_route('classlist.show', $group);
        }

        return to_route('classlist');
    }

    public function show(Student $student)
    {
        $student->load([
            'groups:id,slug,grade,name,school_id',
            'groups.school:id,name',
        ]);

        return Inertia::render('StudentShow', [
            'student' => $student,
        ]);
    }

    public function edit($id) {}

    public function update(Request $request, $id) {

    }

    public function destroy($id) {}
}
