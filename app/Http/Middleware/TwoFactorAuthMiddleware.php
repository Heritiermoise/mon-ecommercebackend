<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifie'
            ], 401);
        }

        // Vérifier si 2FA est activé pour cet utilisateur
        if ($user->two_factor_enabled && !Session::get('2fa_verified')) {
            return response()->json([
                'success' => false,
                'message' => 'Verification 2FA requise',
                'requires_2fa' => true
            ], 403);
        }

        // Vérifier expiration de session (30 minutes d'inactivité)
        $lastActivity = Session::get('last_activity');
        if ($lastActivity && (time() - $lastActivity) > 1800) { // 30 minutes
            Session::flush();
            Auth::logout();
            
            return response()->json([
                'success' => false,
                'message' => 'Session expiree pour inactivite'
            ], 401);
        }

        // Mettre à jour dernière activité
        Session::put('last_activity', time());

        return $next($request);
    }
}