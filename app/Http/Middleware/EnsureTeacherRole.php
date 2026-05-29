<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTeacherRole
{
    public function handle(Request $request, Closure $next): mixed
    {
        $isTeacher = auth()->user()
            ->schools()
            ->wherePivot('role', 'teacher')
            ->exists();

        abort_unless($isTeacher, 403);

        return $next($request);
    }
}
