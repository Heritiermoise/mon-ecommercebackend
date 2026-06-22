<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListeSouhait;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlist = ListeSouhait::where('utilisateur_id', Auth::id())
            ->with(['produit.categorie', 'produit.marque', 'produit.imagePrincipale'])
            ->latest('ajoute_le')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $wishlist->map(fn($w) => [
                'id' => $w->id,
                'produit' => [
                    'id' => $w->produit->id,
                    'nom' => $w->produit->nom,
                    'slug' => $w->produit->slug,
                    'prix' => (float) $w->produit->getPrixFinal(),
                    'en_stock' => $w->produit->isEnStock(),
                    'image' => $w->produit->imagePrincipale ? asset('storage/' . $w->produit->imagePrincipale->url_image) : null,
                ],
                'ajoute_le' => $w->ajoute_le->toISOString(),
            ])
        ]);
    }

    public function add($productId)
    {
        $produit = Produit::findOrFail($productId);

        $existe = ListeSouhait::where('utilisateur_id', Auth::id())
            ->where('produit_id', $produit->id)
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Déjà dans la wishlist'
            ], 400);
        }

        ListeSouhait::create([
            'utilisateur_id' => Auth::id(),
            'produit_id' => $produit->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ajouté à la wishlist'
        ]);
    }

    public function remove($productId)
    {
        ListeSouhait::where('utilisateur_id', Auth::id())
            ->where('produit_id', $productId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Retiré de la wishlist'
        ]);
    }

    public function moveToCart($productId)
    {
        $wishlist = ListeSouhait::where('utilisateur_id', Auth::id())
            ->where('produit_id', $productId)
            ->firstOrFail();

        // Ajouter au panier
        $request = new Request([
            'produit_id' => $productId,
            'quantite' => 1,
        ]);
        $request->setUserResolver(fn() => Auth::user());

        app(CartController::class)->add($request);

        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déplacé vers le panier'
        ]);
    }
}