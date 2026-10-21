<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo('/masuk');
        $middleware->validateCsrfTokens(except: ['webhook/*', 'api/push/*']);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        // Hidupkan ulang login di instance Vercel mana pun dari identitas
        // yang tersimpan di sesi cookie (SQLite lokal tiap instance berbeda).
        $middleware->appendToGroup('web', \App\Http\Middleware\RehydrateAuth::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (\Throwable $e, Request $request) {
            // Biarkan Laravel menangani otentikasi (redirect ke /masuk), validasi, dan error HTTP secara standar
            if ($e instanceof \Illuminate\Auth\AuthenticationException ||
                $e instanceof \Illuminate\Validation\ValidationException ||
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                return null;
            }

            if (env('APP_DEBUG', false) && env('APP_ENV') !== 'production') {
                return response(
                    "<h1>SiniKajian Debug</h1><p><strong>" . get_class($e) . ":</strong> " . e($e->getMessage()) . "</p><pre>" . e($e->getFile() . ':' . $e->getLine()) . "\n\n" . e($e->getTraceAsString()) . "</pre>",
                    500
                );
            }
        });
    })->create();

if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || getenv('VERCEL')) {
    $app->useStoragePath('/tmp/storage');
}

return $app;
