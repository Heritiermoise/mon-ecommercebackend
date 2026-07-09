<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\WelcomeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:120',
                'email' => 'required|email|unique:utilisateurs,email',
                'telephone' => 'nullable|string',
                'mot_de_passe' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'nom' => $request->nom,
                'email' => strtolower(trim($request->email)),
                'telephone' => $request->telephone,
                'mot_de_passe_hash' => Hash::make($request->mot_de_passe),
                'role' => 'client',
                'statut' => 'actif',
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            // Envoyer l'email de bienvenue
            try {
                if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    Mail::to($user->email)->send(new WelcomeMail($user));
                    Log::info('Email de bienvenue envoyé à ' . $user->email);
                }
            } catch (\Exception $e) {
                Log::error('Erreur envoi email bienvenue: ' . $e->getMessage());
            }

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Inscription reussie',
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'role' => $user->role,
                    'statut' => $user->statut,
                ],
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Register error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'mot_de_passe' => 'required|string',
            ]);

            $credentials = [
                'email' => strtolower(trim($request->email)),
                'password' => $request->mot_de_passe,
            ];

            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ], 401);
            }

            $user = JWTAuth::user();

            if ($user->statut === 'banni') {
                JWTAuth::invalidate($token);
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte a ete suspendu'
                ], 403);
            }

            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connexion reussie',
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'role' => $user->role,
                    'statut' => $user->statut,
                ],
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'message' => 'Deconnexion reussie'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'message' => 'Deconnexion reussie'
            ]);
        }
    }

    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de rafraichir le token'
            ], 401);
        }
    }

    public function profile()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifie'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                    'role' => $user->role,
                    'statut' => $user->statut,
                    'last_login_at' => $user->last_login_at,
                    'last_login_ip' => $user->last_login_ip,
                    'points_fidelite' => $user->getPointsFideliteTotal(),
                    'total_depense' => (float) $user->getTotalDepense(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide'
            ], 401);
        }
    }

    public function checkSession()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expiree',
                    'code' => 'SESSION_EXPIRED'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Session valide',
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Session expiree',
                'code' => 'SESSION_EXPIRED'
            ], 401);
        }
    }
}