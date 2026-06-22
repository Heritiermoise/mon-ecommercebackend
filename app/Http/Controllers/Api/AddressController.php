<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdresseLivraison;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    public function index()
    {
        try {
            $addresses = AdresseLivraison::where('utilisateur_id', Auth::id())
                ->orderByDesc('est_defaut')
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $addresses->map(function($a) {
                    return [
                        'id' => $a->id,
                        'nom_complet' => $a->nom_complet,
                        'telephone' => $a->telephone,
                        'adresse' => $a->adresse,
                        'ville' => $a->ville,
                        'code_postal' => $a->code_postal,
                        'instructions' => $a->instructions,
                        'est_defaut' => (bool) $a->est_defaut,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('AddressController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nom_complet' => 'required|string|max:255',
                'telephone' => 'required|string|max:20',
                'adresse' => 'required|string',
                'ville' => 'required|string|max:100',
                'code_postal' => 'nullable|string|max:20',
                'instructions' => 'nullable|string',
                'est_defaut' => 'boolean',
            ]);

            if ($request->est_defaut) {
                AdresseLivraison::where('utilisateur_id', Auth::id())
                    ->update(['est_defaut' => false]);
            }

            $address = AdresseLivraison::create([
                'utilisateur_id' => Auth::id(),
                'nom_complet' => $request->nom_complet,
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'ville' => $request->ville,
                'code_postal' => $request->code_postal,
                'instructions' => $request->instructions,
                'est_defaut' => $request->est_defaut ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Adresse ajoutee',
                'data' => $address
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $address = AdresseLivraison::where('id', $id)
                ->where('utilisateur_id', Auth::id())
                ->firstOrFail();

            $request->validate([
                'nom_complet' => 'sometimes|string|max:255',
                'telephone' => 'sometimes|string|max:20',
                'adresse' => 'sometimes|string',
                'ville' => 'sometimes|string|max:100',
                'code_postal' => 'nullable|string|max:20',
                'instructions' => 'nullable|string',
                'est_defaut' => 'boolean',
            ]);

            if ($request->est_defaut) {
                AdresseLivraison::where('utilisateur_id', Auth::id())
                    ->update(['est_defaut' => false]);
            }

            $address->update($request->only([
                'nom_complet', 'telephone', 'adresse', 'ville',
                'code_postal', 'instructions', 'est_defaut'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Adresse mise a jour',
                'data' => $address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $address = AdresseLivraison::where('id', $id)
                ->where('utilisateur_id', Auth::id())
                ->firstOrFail();

            $address->delete();

            return response()->json([
                'success' => true,
                'message' => 'Adresse supprimee'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function setDefault($id)
    {
        try {
            $address = AdresseLivraison::where('id', $id)
                ->where('utilisateur_id', Auth::id())
                ->firstOrFail();

            AdresseLivraison::where('utilisateur_id', Auth::id())
                ->update(['est_defaut' => false]);

            $address->update(['est_defaut' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Adresse par defaut definie',
                'data' => $address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}