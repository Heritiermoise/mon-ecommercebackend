<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;

class VerifySession
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé',
                    'code' => 'USER_NOT_FOUND'
                ], 401);
            }

            if ($user->statut !== 'actif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte a été suspendu',
                    'code' => 'ACCOUNT_SUSPENDED'
                ], 403);
            }

        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Session expirée. Veuillez vous reconnecter.',
                'code' => 'TOKEN_EXPIRED'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide',
                'code' => 'TOKEN_INVALID'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token manquant',
                'code' => 'TOKEN_MISSING'
            ], 401);
        } catch (\Exception $e) {
            Log::error('VerifySession error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur de session',
                'code' => 'SESSION_ERROR'
            ], 401);
        }

        return $next($request);
    }
}