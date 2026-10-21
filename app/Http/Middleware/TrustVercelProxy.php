<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Percayai header X-Forwarded-* HANYA dari peer langsung (edge Vercel).
 *
 * Menggantikan trustProxies('*') (audit 2026-10-21): dengan '*', klien
 * bisa memalsukan X-Forwarded-For untuk mengelabui rate-limiter berbasis
 * IP dan log. Di Vercel satu-satunya proxy di depan aplikasi adalah edge
 * mereka sendiri = REMOTE_ADDR setiap request.
 */
class TrustVercelProxy
{
    public function handle(Request $request, Closure $next): Response
    {
        $remote = $request->server->get('REMOTE_ADDR');
        if (is_string($remote) && $remote !== '') {
            $request->setTrustedProxies(
                [$remote],
                Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
            );
        }

        return $next($request);
    }
}
