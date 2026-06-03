<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchoolAcademicYearController extends Controller
{
    public function index(School $school)
    {
        $today = now()->toDateString();

        $years = $school->academicYears()
            ->orderByDesc('year')
            ->get()
            ->map(fn ($y) => [
                'id'         => $y->id,
                'year'       => $y->year,
                'start_date' => $y->pivot->start_date,
                'end_date'   => $y->pivot->end_date,
                'is_current' => $today >= $y->pivot->start_date && $today <= $y->pivot->end_date,
            ]);

        return Inertia::render('admin/AcademicYears', [
            'school' => $school->only('id', 'name', 'slug'),
            'years'  => $years,
        ]);
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'year'       => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
        ]);

        $year = AcademicYear::firstOrCreate(['year' => $validated['year']]);

        $school->academicYears()->syncWithoutDetaching([
            $year->id => [
                'start_date' => $validated['start_date'],
                'end_date'   => $validated['end_date'],
            ],
        ]);

        return back();
    }

    public function update(Request $request, School $school, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
        ]);

        $school->academicYears()->updateExistingPivot($academicYear->id, $validated);

        return back();
    }
}