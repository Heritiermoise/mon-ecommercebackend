<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use App\Services\ImageUploadService;
use App\Models\Produit;
use App\Models\ImageProduit;
use App\Models\MouvementStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockController extends Controller
{
    /**
     * Ajouter du stock (intelligent)
     */
    public function ajouter(Request $request)
    {
        try {
            $request->validate([
                'nom' => 'required|string|max:255',
                'categorie_id' => 'required|exists:categories,id',
                'marque_id' => 'required|exists:marques,id',
                'quantite_stock' => 'required|integer|min:1',
                'prix' => 'required|numeric|min:0',
                'prix_remise' => 'nullable|numeric|min:0',
                'description' => 'nullable|string',
                'statut' => 'required|in:actif,inactif',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            ]);

            $result = StockService::ajouterStock($request->all(), Auth::id());

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            // Upload de l'image si fournie
            if ($request->hasFile('image')) {
                $imageResult = ImageUploadService::upload($request->file('image'), 'produits');
                
                if ($imageResult['success']) {
                    ImageProduit::create([
                        'produit_id' => $result['produit']->id,
                        'url_image' => $imageResult['url'],
                        'chemin_fichier' => $imageResult['path'],
                        'est_principale' => true,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'action' => $result['action'],
                    'produit' => $result['produit']->load(['categorie', 'marque', 'imagePrincipale']),
                    'stock_avant' => $result['stock_avant'],
                    'stock_apres' => $result['stock_apres'],
                ]
            ], $result['action'] === 'created' ? 201 : 200);
        } catch (\Exception $e) {
            Log::error('StockController@ajouter: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajuster le stock manuellement
     */
    public function ajuster(Request $request, $produitId)
    {
        try {
            $request->validate([
                'quantite' => 'required|integer|min:0',
                'note' => 'nullable|string|max:500',
            ]);

            $result = StockService::ajusterStock(
                $produitId,
                $request->quantite,
                Auth::id(),
                $request->note
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Historique des mouvements d'un produit
     */
    public function historique($produitId)
    {
        try {
            $historique = StockService::getHistoriqueProduit($produitId);
            
            return response()->json([
                'success' => true,
                'data' => $historique,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques de stock
     */
    public function statistiques()
    {
        try {
            $stats = StockService::getStatistiques();
            
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tous les mouvements de stock
     */
    public function tousMouvements(Request $request)
    {
        try {
            $query = MouvementStock::with(['produit', 'utilisateur']);

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('produit_id')) {
                $query->where('produit_id', $request->produit_id);
            }

            $mouvements = $query->orderByDesc('created_at')->paginate(30);

            $formatted = $mouvements->getCollection()->map(function($m) {
                return [
                    'id' => $m->id,
                    'produit' => $m->produit ? [
                        'id' => $m->produit->id,
                        'nom' => $m->produit->nom,
                    ] : null,
                    'utilisateur' => $m->utilisateur ? $m->utilisateur->nom : 'Système',
                    'type' => $m->type,
                    'quantite' => $m->quantite,
                    'stock_avant' => $m->stock_avant,
                    'stock_apres' => $m->stock_apres,
                    'reference' => $m->reference,
                    'note' => $m->note,
                    'date' => $m->created_at->format('d/m/Y H:i'),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formatted,
                'pagination' => [
                    'total' => $mouvements->total(),
                    'per_page' => $mouvements->perPage(),
                    'current_page' => $mouvements->currentPage(),
                    'last_page' => $mouvements->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}