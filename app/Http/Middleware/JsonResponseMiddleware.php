<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class JsonResponseMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
        }

        $response = $next($request);

        if ($request->is('api/*') && !$response->headers->get('Content-Type') === 'application/json') {
            if (!($response->getContent() && json_decode($response->getContent()))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur serveur interne'
                ], 500);
            }
        }

        return $response;
    }
}