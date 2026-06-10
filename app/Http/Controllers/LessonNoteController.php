<?php

namespace App\Http\Controllers;

use App\Models\LessonNote;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LessonNoteController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $date = Carbon::parse($data['date'])->toDateString();
        $notes = $data['notes'] ?? '';

        LessonNote::updateOrCreate(
            ['lesson_id' => $data['lesson_id'], 'date' => $date],
            ['notes' => $notes],
        );

        return back();
    }
}
