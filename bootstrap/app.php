<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthenticate::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'store.owner' => \App\Http\Middleware\EnsureStoreOwnership::class,
            'force.json' => \App\Http\Middleware\ForceJsonResponse::class,
        ]);
        $middleware->append(HandleCors::class);

        $middleware->appendToGroup('api', [
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            'force.json',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated', 'data' => null], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'data' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Resource not found', 'data' => null], 404);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Forbidden', 'data' => null], 403);
            }
        });
    })->create();
