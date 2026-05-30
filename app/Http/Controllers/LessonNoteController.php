<?php

namespace App\Http\Controllers;

use App\Models\LessonNote;
use Illuminate\Http\Request;

class LessonNoteController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'date'      => ['required', 'date'],
            'notes'     => ['nullable', 'string', 'max:10000'],
        ]);

        LessonNote::updateOrCreate(
            ['lesson_id' => $validated['lesson_id'], 'date' => $validated['date']],
            ['notes'     => $validated['notes'] ?? ''],
        );

        return back();
    }
}