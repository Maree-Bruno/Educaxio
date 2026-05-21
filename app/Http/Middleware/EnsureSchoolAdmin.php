<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;

class EnsureSchoolAdmin
{
    public function handle(Request $request, Closure $next): mixed
    {
        $school = $request->route('school');

        if (! $school instanceof School) {
            abort(404);
        }

        $isAdmin = auth()->user()
            ->schools()
            ->where('schools.id', $school->id)
            ->wherePivot('role', 'admin')
            ->exists();

        abort_unless($isAdmin, 403);

        return $next($request);
    }
}