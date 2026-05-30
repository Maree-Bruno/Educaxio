<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use Illuminate\Http\Request;

class ClassSessionController extends Controller
{
    public function updateNotes(Request $request)
    {
        $validated = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'date'      => ['required', 'date'],
            'notes'     => ['nullable', 'string', 'max:10000'],
        ]);

        $session = ClassSession::firstOrCreate(
            ['lesson_id' => $validated['lesson_id'], 'date' => $validated['date']],
        );

        $session->update(['notes' => $validated['notes'] ?? null]);

        return back();
    }
}