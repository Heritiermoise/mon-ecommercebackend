<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SecurityService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class TwoFactorController extends Controller
{
    public function enable(Request $request)
    {
        try {
            $request->validate([
                'password' => 'required|string',
            ]);

            $user = Auth::user();

            if (!Hash::check($request->password, $user->mot_de_passe_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mot de passe incorrect'
                ], 401);
            }

            $secret = SecurityService::generate2FACode();

            $user->update([
                'two_factor_enabled' => true,
                'two_factor_secret' => encrypt($secret),
                'two_factor_last_verified' => now(),
            ]);

            // Envoyer le code par email
            try {
                $emailService = new EmailService();
                $emailService->envoyer2FA($user, $secret);
            } catch (\Exception $e) {
                Log::error('Erreur envoi 2FA: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => '2FA active. Un code a ete envoye par email.',
                'secret' => $secret
            ]);
        } catch (\Exception $e) {
            Log::error('enable2FA error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verify(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string|size:6',
            ]);

            $user = Auth::user();

            if (!$user->two_factor_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => '2FA non active'
                ], 400);
            }

            $secret = decrypt($user->two_factor_secret);

            if ($request->code !== $secret) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code invalide'
                ], 401);
            }

            Session::put('2fa_verified', true);

            $user->update([
                'two_factor_last_verified' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification 2FA reussie'
            ]);
        } catch (\Exception $e) {
            Log::error('verify2FA error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function disable(Request $request)
    {
        try {
            $request->validate([
                'password' => 'required|string',
            ]);

            $user = Auth::user();

            if (!Hash::check($request->password, $user->mot_de_passe_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mot de passe incorrect'
                ], 401);
            }

            $user->update([
                'two_factor_enabled' => false,
                'two_factor_secret' => null,
            ]);

            Session::forget('2fa_verified');

            return response()->json([
                'success' => true,
                'message' => '2FA desactivee'
            ]);
        } catch (\Exception $e) {
            Log::error('disable2FA error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resendCode()
    {
        try {
            $user = Auth::user();

            if (!$user->two_factor_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => '2FA non active'
                ], 400);
            }

            $secret = SecurityService::generate2FACode();

            $user->update([
                'two_factor_secret' => encrypt($secret),
            ]);

            try {
                $emailService = new EmailService();
                $emailService->envoyer2FA($user, $secret);
            } catch (\Exception $e) {
                Log::error('Erreur renvoi 2FA: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Nouveau code envoye par email'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}