<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolJoinRequest;

class JoinRequestController extends Controller
{
    public function approve(School $school, SchoolJoinRequest $joinRequest)
    {
        abort_unless($joinRequest->school_id === $school->id, 403);

        $school->users()->syncWithoutDetaching([
            $joinRequest->user_id => ['role' => 'teacher'],
        ]);

        $joinRequest->update(['status' => 'approved']);

        return back();
    }

    public function reject(School $school, SchoolJoinRequest $joinRequest)
    {
        abort_unless($joinRequest->school_id === $school->id, 403);

        $joinRequest->update(['status' => 'rejected']);

        return back();
    }
}