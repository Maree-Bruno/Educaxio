<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LessonAssignmentController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $schoolIds = $user->schools()->pluck('schools.id');
        $allowedGroupIds = Group::whereIn('school_id', $schoolIds)->pluck('id');

        $validated = $request->validate([
            'lesson_ids' => ['array'],
            'lesson_ids.*' => [
                'integer',
                Rule::exists('lessons', 'id')->whereIn('group_id', $allowedGroupIds),
            ],
        ]);

        $user->lessons()->sync($validated['lesson_ids'] ?? []);

        return back();
    }
}
