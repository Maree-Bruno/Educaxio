<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Schedule;
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

        $currentYear = AcademicYear::whereHas('schools', fn ($q) =>
            $q->where('schools.id', $school->id)
              ->whereNull('academic_year_school.archived_at')
        )->latest()->first();

        if ($currentYear) {
            Schedule::firstOrCreate([
                'user_id'          => $joinRequest->user_id,
                'school_id'        => $school->id,
                'academic_year_id' => $currentYear->id,
            ]);
        }

        return to_route('dashboard');
    }

    public function reject(School $school, SchoolJoinRequest $joinRequest)
    {
        abort_unless($joinRequest->school_id === $school->id, 403);

        $joinRequest->update(['status' => 'rejected']);

        return to_route('dashboard');
    }
}
