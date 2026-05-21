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
        ]);

        return Inertia::render('StudentShow', [
            'student' => $student,
        ]);
    }

    public function edit($id) {}

    public function update(Request $request, Student $student)
    {
        abort_unless($this->userSchoolIds()->contains($student->school_id), 403);

        $validated = $request->validate([
            'lastname'  => ['required', 'string', 'max:100'],
            'firstname' => ['required', 'string', 'max:100'],
            'email'     => ['nullable', 'email', 'max:255'],
        ]);

        $student->update($validated);

        return back();
    }

    public function destroy($id) {}
}