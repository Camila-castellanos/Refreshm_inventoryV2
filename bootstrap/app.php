<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Console\Commands\ExtractProductModels::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SetUserTimezone::class,
        ]);

        // // Registrar alias para el middleware de verificación de roles
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckUserRole::class,
            'page.permission' => \App\Http\Middleware\CheckPagePermissions::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, $request) {
            if ($response->getStatusCode() === 419) {
                return response()->json([
                    'message' => 'Page expired, please refresh.',
                ], 419);
            }

            return $response;
        });
    })
    ->create();
