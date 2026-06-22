<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Forcer le Content-Type JSON
        if ($request->is('api/*') || $request->wantsJson()) {
            $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        }

        return $response;
    }
}