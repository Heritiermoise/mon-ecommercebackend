<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = User::query();

            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            if ($request->has('statut')) {
                $query->where('statut', $request->statut);
            }

            $users = $query->latest()->paginate(20);

            $formattedUsers = $users->getCollection()->map(function($u) {
                return [
                    'id' => $u->id,
                    'nom' => $u->nom,
                    'email' => $u->email,
                    'telephone' => $u->telephone,
                    'role' => $u->role,
                    'statut' => $u->statut,
                    'created_at' => $u->created_at ? $u->created_at->format('d/m/Y H:i') : '',
                    'commandes_count' => $u->commandes()->count(),
                    'total_depense' => (float) $u->getTotalDepense(),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formattedUsers,
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('UserController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            if ($user->role === 'super_administrateur') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer un super administrateur'
                ], 403);
            }
            $user->delete();
            return response()->json(['success' => true, 'message' => 'Utilisateur supprime']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $user = User::findOrFail($id);
            if ($user->role === 'super_administrateur') {
                return response()->json(['success' => false, 'message' => 'Impossible'], 403);
            }
            $newStatus = $user->statut === 'actif' ? 'banni' : 'actif';
            $user->update(['statut' => $newStatus]);
            return response()->json(['success' => true, 'message' => 'Statut mis a jour', 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function changeRole(Request $request, $id)
    {
        try {
            $request->validate(['role' => 'required|in:client,administrateur,super_administrateur']);
            $user = User::findOrFail($id);
            $user->update(['role' => $request->role]);
            return response()->json(['success' => true, 'message' => 'Role mis a jour', 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}