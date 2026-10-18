<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers untuk proteksi XSS, clickjacking, dll.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(), microphone=()');

        // CSP: izinkan self, Tailwind CDN, Google Maps, Mapbox, Fonts, Turnstile
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://maps.googleapis.com https://maps.gstatic.com https://api.mapbox.com https://challenges.cloudflare.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com https://api.mapbox.com; " .
               "img-src 'self' data: blob: https:; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "connect-src 'self' https://*.supabase.co https://maps.googleapis.com https://api.mapbox.com https://events.mapbox.com; " .
               "frame-src https://challenges.cloudflare.com; " .
               "frame-ancestors 'self'; " .
               "worker-src 'self' blob:;";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
