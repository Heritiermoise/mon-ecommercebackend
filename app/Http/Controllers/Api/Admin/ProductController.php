<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Models\ImageProduit;
use App\Services\ImageUploadService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Produit::with(['categorie', 'marque', 'imagePrincipale']);

            if ($request->has('search')) {
                $query->where('nom', 'like', '%' . $request->search . '%');
            }

            $products = $query->latest()->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $products->map(function($p) {
                    return [
                        'id' => $p->id,
                        'nom' => $p->nom,
                        'slug' => $p->slug,
                        'prix' => (float) $p->prix,
                        'prix_remise' => $p->prix_remise ? (float) $p->prix_remise : null,
                        'quantite_stock' => (int) $p->quantite_stock,
                        'statut' => $p->statut,
                        'categorie' => $p->categorie,
                        'marque' => $p->marque,
                        'image_principale' => $p->imagePrincipale ? $p->imagePrincipale->url_image : null,
                    ];
                }),
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        
        try {
            $request->validate([
                'nom' => 'required|string|max:255',
                'description' => 'nullable|string',
                'prix' => 'required|numeric|min:0',
                'prix_remise' => 'nullable|numeric|min:0',
                'quantite_stock' => 'required|integer|min:0',
                'categorie_id' => 'required|exists:categories,id',
                'marque_id' => 'required|exists:marques,id',
                'statut' => 'required|in:actif,inactif',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            ]);

            $produit = Produit::create([
                'nom' => $request->nom,
                'description' => $request->description,
                'prix' => $request->prix,
                'prix_remise' => $request->prix_remise,
                'quantite_stock' => $request->quantite_stock,
                'categorie_id' => $request->categorie_id,
                'marque_id' => $request->marque_id,
                'statut' => $request->statut,
            ]);

            // Upload image principale
            if ($request->hasFile('image')) {
                $result = ImageUploadService::upload($request->file('image'), 'produits');
                
                if ($result['success']) {
                    ImageProduit::create([
                        'produit_id' => $produit->id,
                        'url_image' => $result['url'],
                        'chemin_fichier' => $result['path'],
                        'est_principale' => true,
                    ]);
                }
            }

            // Upload images secondaires
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $result = ImageUploadService::upload($file, 'produits');
                    if ($result['success']) {
                        ImageProduit::create([
                            'produit_id' => $produit->id,
                            'url_image' => $result['url'],
                            'chemin_fichier' => $result['path'],
                            'est_principale' => false,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès',
                'data' => $produit->load(['categorie', 'marque', 'images'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductController@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        
        try {
            $produit = Produit::findOrFail($id);

            $request->validate([
                'nom' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'prix' => 'sometimes|numeric|min:0',
                'prix_remise' => 'nullable|numeric|min:0',
                'quantite_stock' => 'sometimes|integer|min:0',
                'categorie_id' => 'sometimes|exists:categories,id',
                'marque_id' => 'sometimes|exists:marques,id',
                'statut' => 'sometimes|in:actif,inactif',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            ]);

            $produit->update($request->only([
                'nom', 'description', 'prix', 'prix_remise',
                'quantite_stock', 'categorie_id', 'marque_id', 'statut'
            ]));

            // Upload nouvelle image principale
            if ($request->hasFile('image')) {
                $oldImage = $produit->imagePrincipale;
                if ($oldImage) {
                    ImageUploadService::delete($oldImage->chemin_fichier);
                    $oldImage->delete();
                }

                $result = ImageUploadService::upload($request->file('image'), 'produits');
                if ($result['success']) {
                    ImageProduit::create([
                        'produit_id' => $produit->id,
                        'url_image' => $result['url'],
                        'chemin_fichier' => $result['path'],
                        'est_principale' => true,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour',
                'data' => $produit->load(['categorie', 'marque', 'images'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $produit = Produit::findOrFail($id);

            foreach ($produit->images as $image) {
                ImageUploadService::delete($image->chemin_fichier);
                $image->delete();
            }

            $produit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Produit supprimé'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function uploadImage(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            ]);

            $result = ImageUploadService::upload($request->file('image'), 'produits');

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Image uploadée',
                    'data' => [
                        'url' => $result['url'],
                        'path' => $result['path'],
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        } catch (\Exception $e) {
            Log::error('Upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteImage($productId, $imageId)
    {
        try {
            $image = ImageProduit::where('id', $imageId)
                ->where('produit_id', $productId)
                ->firstOrFail();

            ImageUploadService::delete($image->chemin_fichier);
            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'Image supprimée'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function setMainImage($productId, $imageId)
    {
        try {
            ImageProduit::where('produit_id', $productId)
                ->update(['est_principale' => false]);

            $image = ImageProduit::where('id', $imageId)
                ->where('produit_id', $productId)
                ->firstOrFail();

            $image->update(['est_principale' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Image principale définie'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}