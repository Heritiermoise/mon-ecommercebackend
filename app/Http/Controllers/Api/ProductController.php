<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Produit::with(['categorie', 'marque', 'imagePrincipale'])
                ->where('statut', 'actif');

            if ($request->has('search')) {
                $query->where('nom', 'like', '%' . $request->search . '%');
            }

            $produits = $query->latest()->paginate(12);
            $items = $produits->getCollection()->map(function ($p) {
                return [
                    'id' => (int) $p->id,
                    'nom' => $p->nom,
                    'slug' => $p->slug,
                    'description' => $p->description,
                    'prix' => (float) $p->prix,
                    'prix_remise' => $p->prix_remise ? (float) $p->prix_remise : null,
                    'quantite_stock' => (int) $p->quantite_stock,
                    'categorie' => $p->categorie ? [
                        'id' => (int) $p->categorie->id,
                        'nom' => $p->categorie->nom,
                    ] : null,
                    'marque' => $p->marque ? [
                        'id' => (int) $p->marque->id,
                        'nom' => $p->marque->nom,
                    ] : null,
                    'image_principale' => $p->image_display_url,
                    'note_moyenne' => (float) ($p->note_moyenne ?? 0),
                    'nombre_avis' => (int) ($p->nombre_avis ?? 0),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $items,
                'pagination' => [
                    'total' => (int) $produits->total(),
                    'per_page' => (int) $produits->perPage(),
                    'current_page' => (int) $produits->currentPage(),
                    'last_page' => (int) $produits->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('ProductController error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($slug)
    {
        try {
            $produit = Produit::where('slug', $slug)
                ->where('statut', 'actif')
                ->first();

            if (!$produit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouve'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => (int) $produit->id,
                    'nom' => $produit->nom,
                    'slug' => $produit->slug,
                    'description' => $produit->description,
                    'prix' => (float) $produit->prix,
                    'prix_remise' => $produit->prix_remise ? (float) $produit->prix_remise : null,
                    'quantite_stock' => (int) $produit->quantite_stock,
                    'categorie' => $produit->categorie,
                    'marque' => $produit->marque,
                    'image_principale' => $produit->image_display_url,
                    'note_moyenne' => (float) ($produit->note_moyenne ?? 0),
                    'nombre_avis' => (int) ($produit->nombre_avis ?? 0),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('ProductController@show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}