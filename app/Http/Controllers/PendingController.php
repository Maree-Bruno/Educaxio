<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolJoinRequest;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PendingController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $requests = SchoolJoinRequest::where('user_id', $user->id)
            ->with('school:id,name,slug')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'school' => ['id' => $r->school->id, 'name' => $r->school->name],
                'status' => $r->status,
            ]);

        $requestedSchoolIds = $requests->pluck('school.id');

        return Inertia::render('Pending', [
            'requests' => $requests,
            'schools' => School::orderBy('name')
                ->whereNotIn('id', $requestedSchoolIds)
                ->get(['id', 'name']),
            'allSubjects' => Subject::orderBy('name')->get(['id', 'name']),
            'userSubjects' => $user->subjects()->pluck('subjects.id'),
            'user' => $user,
        ]);
    }

    public function requestSchool(Request $request)
    {
        $validated = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
        ]);

        $user = auth()->user();

        abort_if(
            SchoolJoinRequest::where('user_id', $user->id)
                ->where('school_id', $validated['school_id'])
                ->exists(),
            422,
        );

        SchoolJoinRequest::create([
            'user_id' => $user->id,
            'school_id' => $validated['school_id'],
            'status' => 'pending',
        ]);

        return to_route('pending');
    }

    public function cancelRequest(SchoolJoinRequest $joinRequest)
    {
        abort_unless($joinRequest->user_id === auth()->id(), 403);
        abort_unless($joinRequest->status === 'pending', 422);

        $joinRequest->delete();

        return to_route('pending');
    }

    public function syncSubjects(Request $request)
    {
        $validated = $request->validate([
            'subject_ids' => ['present', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ]);

        auth()->user()->subjects()->sync($validated['subject_ids']);

        return to_route('pending');
    }
}
