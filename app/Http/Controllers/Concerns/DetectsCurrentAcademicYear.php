<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AcademicYear;
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
}