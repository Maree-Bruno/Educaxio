<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AcademicYear;
use App\Models\SchoolJoinRequest;
use App\Models\SchoolSlotTime;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait DetectsCurrentAcademicYear
{
    private function currentAcademicYearId(Collection $schoolIds): ?int
    {
        $today = now()->toDateString();

        return AcademicYear::whereHas('schools', fn ($q) =>
            $q->whereIn('schools.id', $schoolIds)
              ->where('academic_year_school.start_date', '<=', $today)
              ->where('academic_year_school.end_date', '>=', $today)
        )->value('id');
    }

    private function selectedAcademicYearId(?int $currentYearId, Request $request): ?int
    {
        return $request->filled('year') ? $request->integer('year') : $currentYearId;
    }

    private function academicYearsForSchools(Collection $schoolIds, ?int $currentYearId): Collection
    {
        return AcademicYear::whereHas('schools', fn ($q) => $q->whereIn('schools.id', $schoolIds))
            ->with(['schools' => fn ($q) => $q->whereIn('schools.id', $schoolIds)])
            ->orderByDesc('year')
            ->get(['id', 'year'])
            ->map(fn ($y) => [
                'id'          => $y->id,
                'year'        => $y->year,
                'is_current'  => $y->id === $currentYearId,
                'is_archived' => $y->schools->every(fn ($s) => $s->pivot->archived_at !== null),
            ]);
    }

    private function schoolSlotTimes(Collection $schoolIds): Collection
    {
        return SchoolSlotTime::whereIn('school_id', $schoolIds)
            ->get()
            ->groupBy('school_id')
            ->map(fn ($rows) => $rows->keyBy('schedule_slot_id')->map(fn ($r) => [
                'start_time' => substr($r->start_time, 0, 5),
                'end_time'   => substr($r->end_time, 0, 5),
            ]));
    }

    private function pendingJoinRequests(int $schoolId, ?int $limit = null): Collection
    {
        $query = SchoolJoinRequest::where('school_id', $schoolId)
            ->where('status', 'pending')
            ->with(['user:id,name,email', 'user.subjects:id,name'])
            ->latest();

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()->map(fn ($r) => [
            'id'       => $r->id,
            'user'     => ['id' => $r->user->id, 'name' => $r->user->name, 'email' => $r->user->email],
            'subjects' => $r->user->subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
        ]);
    }
}