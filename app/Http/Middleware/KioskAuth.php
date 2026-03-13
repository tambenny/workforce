<?php

namespace App\Http\Middleware;

use App\Models\Kiosk;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KioskAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-KIOSK-TOKEN')
            ?: $request->query('token')
            ?: $request->cookie('kiosk_token');

        if (! $token) {
            abort(401, 'Kiosk token missing.');
        }

        $kiosk = Kiosk::with('location')
            ->where('kiosk_token_hash', hash('sha256', $token))
            ->where('is_active', true)
            ->first();

        if (! $kiosk) {
            abort(403, 'Invalid or inactive kiosk token.');
        }

        $allowedIp = $kiosk->location?->allowed_ip;
        if ($allowedIp && $request->ip() !== $allowedIp) {
            abort(403, 'Kiosk access must be performed from the store machine network.');
        }

        $kiosk->forceFill(['last_seen_at' => now()])->save();
        $request->attributes->set('kiosk', $kiosk);

        if ($request->query('token')) {
            $cookie = cookie(
                'kiosk_token',
                $token,
                60 * 24 * 30,
                null,
                null,
                $request->isSecure(),
                true,
                false,
                'Lax'
            );

            $query = $request->query();
            unset($query['token']);
            $targetUrl = $request->url();
            if (! empty($query)) {
                $targetUrl .= '?' . http_build_query($query);
            }

            return redirect()->to($targetUrl)->withCookie($cookie);
        }

        $response = $next($request);

        return $response;
    }
}
