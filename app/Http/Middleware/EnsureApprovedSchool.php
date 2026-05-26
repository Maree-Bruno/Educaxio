<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureApprovedSchool
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (auth()->user()->schools()->exists()) {
            return $next($request);
        }

        return redirect()->route('pending');
    }
}