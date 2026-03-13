<?php

namespace App\Http\Middleware;

use App\Models\SecurityWarning;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogIpMismatch
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user()->loadMissing('location');
            $location = $user->location;
            $ip = (string) $request->ip();

            if ($location && $location->allowed_ip && $ip !== $location->allowed_ip) {
                $exists = SecurityWarning::query()
                    ->where('user_id', $user->id)
                    ->where('warning_type', 'LOGIN_IP_MISMATCH')
                    ->where('ip_address', $ip)
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if (! $exists) {
                    SecurityWarning::create([
                        'user_id' => $user->id,
                        'location_id' => $location->id,
                        'warning_type' => 'LOGIN_IP_MISMATCH',
                        'ip_address' => $ip,
                        'message' => "Login IP {$ip} does not match registered store machine IP {$location->allowed_ip}.",
                    ]);
                }
            }
        }

        return $next($request);
    }
}
