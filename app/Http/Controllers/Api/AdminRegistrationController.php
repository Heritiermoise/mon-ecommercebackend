<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminRegistrationController extends Controller
{
    public function check()
    {
        try {
            $adminCount = User::whereIn('role', ['administrateur', 'super_administrateur'])->count();
            
            return response()->json([
                'success' => true,
                'available' => $adminCount === 0,
                'message' => $adminCount === 0 
                    ? 'Inscription admin disponible' 
                    : 'Inscription admin fermee'
            ]);
        } catch (\Exception $e) {
            Log::error('AdminRegistrationController@check: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'available' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $adminCount = User::whereIn('role', ['administrateur', 'super_administrateur'])->count();
            
            if ($adminCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscription admin fermee'
                ], 403);
            }

            $request->validate([
                'nom' => 'required|string|max:120',
                'email' => 'required|email|unique:utilisateurs,email',
                'telephone' => 'nullable|string',
                'mot_de_passe' => 'required|string|min:8|confirmed',
                'role' => 'required|in:administrateur,super_administrateur',
            ]);

            $user = User::create([
                'nom' => $request->nom,
                'email' => strtolower(trim($request->email)),
                'telephone' => $request->telephone,
                'mot_de_passe_hash' => Hash::make($request->mot_de_passe),
                'role' => $request->role,
                'statut' => 'actif',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscription admin reussie',
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('AdminRegistrationController@register: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}