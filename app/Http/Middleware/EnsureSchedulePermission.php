<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchedulePermission
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        if (! $user->hasSchedulePermission($ability)) {
            abort(403, 'Insufficient schedule permission.');
        }

        return $next($request);
    }
}
