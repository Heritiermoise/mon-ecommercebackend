<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdvancedRateLimiter
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $path = $request->path();
        
        // Rate limiting par endpoint
        $limits = [
            'login' => ['attempts' => 5, 'decay' => 15], // 5 tentatives par 15 minutes
            'register' => ['attempts' => 3, 'decay' => 60], // 3 inscriptions par heure
            'api/*' => ['attempts' => 60, 'decay' => 1], // 60 requêtes par minute
            'admin/*' => ['attempts' => 100, 'decay' => 1], // 100 requêtes par minute
            'payment/*' => ['attempts' => 10, 'decay' => 5], // 10 tentatives par 5 minutes
        ];

        foreach ($limits as $pattern => $config) {
            if (fnmatch($pattern, $path)) {
                $key = $pattern . ':' . $ip;
                
                if (RateLimiter::tooManyAttempts($key, $config['attempts'])) {
                    $retryAfter = RateLimiter::availableIn($key);
                    
                    Log::warning("Rate limit depasse: IP=$ip, Path=$path, Retry=$retryAfter");
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Trop de requetes. Veuillez reessayer dans ' . $retryAfter . ' secondes.',
                        'retry_after' => $retryAfter
                    ], 429)->withHeaders([
                        'Retry-After' => $retryAfter,
                        'X-RateLimit-Limit' => $config['attempts'],
                        'X-RateLimit-Remaining' => 0,
                    ]);
                }
                
                RateLimiter::hit($key, $config['decay'] * 60);
                
                // Ajouter headers informatifs
                $response = $next($request);
                $response->headers->set('X-RateLimit-Limit', $config['attempts']);
                $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, $config['attempts']));
                
                return $response;
            }
        }

        return $next($request);
    }
}