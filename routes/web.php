<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Route principale - Redirection vers le frontend
Route::get('/', function () {
    $frontendUrl = env('FRONTEND_URL', 'https://mon-ecommercefrontend.vercel.app');
    
    // Si FRONTEND_URL est défini, rediriger vers le frontend
    if ($frontendUrl && $frontendUrl !== 'https://mon-ecommercefrontend.vercel.app') {
        return redirect($frontendUrl);
    }
    
    // Sinon, afficher une page d'accueil personnalisée
    return view('welcome-custom');
});

// Health check pour Render
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'ShopPro Backend API',
        'timestamp' => now(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'database' => (function() {
            try {
                DB::connection()->getPdo();
                return 'connected';
            } catch (\Exception $e) {
                return 'disconnected: ' . $e->getMessage();
            }
        })(),
    ]);
});

// Route API info
Route::get('/api', function () {
    return response()->json([
        'name' => 'ShopPro API',
        'version' => '1.0.0',
        'endpoints' => [
            'products' => '/api/products',
            'categories' => '/api/categories',
            'brands' => '/api/brands',
            'auth' => '/api/login, /api/register',
        ],
    ]);
});