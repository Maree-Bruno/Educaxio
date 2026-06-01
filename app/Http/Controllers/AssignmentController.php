<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ComputesNextOccurrence;
use App\Models\Assignment;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    use ComputesNextOccurrence;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'type' => ['required', Rule::in(['homework', 'test'])],
            'title' => ['required', 'string', 'max:255'],
            'scheduled_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $lesson = Lesson::with('scheduleEntries')->findOrFail($validated['lesson_id']);

        $validated['scheduled_date'] ??= $this->nextOccurrence($lesson);
        $validated['created_by'] = auth()->id();

        Assignment::create($validated);

        return back();
    }

    public function update(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['homework', 'test'])],
            'title' => ['required', 'string', 'max:255'],
            'scheduled_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $assignment->update($validated);

        return back();
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();

        return back();
    }
}
