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
    ->withMiddleware(function (Middleware $middleware) {
        // Exclure les routes API du CSRF
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        
        // Enregistrer les alias de middleware
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'json.response' => \App\Http\Middleware\ForceJsonResponse::class,
        ]);
        
        $middleware->append(\App\Http\Middleware\ForceJsonResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->wantsJson();
        });
    })->create();




