<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\DetectsCurrentAcademicYear;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolJoinRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeacherController extends Controller
{
    use DetectsCurrentAcademicYear;

    public function index(Request $request, School $school)
    {
        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';
        $sort = in_array($request->string('sort')->toString(), ['name', 'email'])
            ? $request->string('sort')->toString()
            : 'name';

        $query = $school->users()
            ->wherePivot('role', 'teacher')
            ->orderBy("users.{$sort}", $dir);

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where(fn ($q) => $q
                ->where('users.name', 'like', "%{$search}%")
                ->orWhere('users.email', 'like', "%{$search}%"),
            );
        }

        $teachers = $query
            ->select(['users.id', 'users.name', 'users.email'])
            ->paginate(20)
            ->withQueryString();

        $schoolId = $school->id;
        $currentYearId = $this->currentAcademicYearId(collect([$schoolId]));

        $teachers->getCollection()->load([
            'lessons' => fn ($q) => $q
                ->whereHas('group', fn ($sq) => $sq
                    ->where('school_id', $schoolId)
                    ->when($currentYearId, fn ($g) => $g->where('academic_year_id', $currentYearId)),
                )
                ->with('group:id,grade,name', 'subject:id,name')
                ->select('lessons.id', 'lessons.group_id', 'lessons.subject_id', 'lessons.lm_level'),
            'subjects:id,name',
        ]);

        $joinRequests = SchoolJoinRequest::where('school_id', $school->id)
            ->where('status', 'pending')
            ->with([
                'user:id,name,email',
                'user.subjects:id,name',
            ])
            ->latest()
            ->get();

        return Inertia::render('admin/Teachers', [
            'school' => $school->only('id', 'name', 'slug'),
            'teachers' => $teachers,
            'filters' => (object) $request->only(['search', 'sort', 'dir']),
            'joinRequests' => $joinRequests->map(fn ($r) => [
                'id' => $r->id,
                'user' => ['id' => $r->user->id, 'name' => $r->user->name, 'email' => $r->user->email],
                'subjects' => $r->user->subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            ]),
        ]);
    }

    public function destroy(School $school, User $user)
    {
        $this->authorize('update', $school);

        $school->users()->detach($user->id);

        return back();
    }
}
