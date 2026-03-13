<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $allowed = array_map('trim', explode(',', $roles));

        if (! in_array(auth()->user()->role, $allowed, true)) {
            abort(403, 'Insufficient role.');
        }

        return $next($request);
    }
}
