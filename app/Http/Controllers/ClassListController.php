<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassListController extends Controller
{
    public function index()
    {
        $groups = Group::with(['academicYear'])->withCount('students')->get();

        return Inertia::render('ClassList', [
            'groups' => $groups,
        ]);
    }

    public function create(): void {}

    public function store(Request $request): void {}

    public function show($id): void {}

    public function edit($id): void {}

    public function update(Request $request, $id): void {}

    public function destroy($id): void {}
}
