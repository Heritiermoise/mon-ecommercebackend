<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Panier;
use App\Models\ArticlePanier;
use App\Models\Produit;
use App\Models\CodePromo;
use App\Models\UtilisationCodePromo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Récupérer le panier de l'utilisateur
     */
    public function index()
    {
        $panier = $this->getOrCreatePanier();
        $this->loadCartRelations($panier);

        return response()->json([
            'success' => true,
            'data' => $this->formatCart($panier)
        ]);
    }

    /**
     * Ajouter un produit au panier
     */
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
            'quantite' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $produit = Produit::findOrFail($request->produit_id);

        if ($produit->quantite_stock < $request->quantite) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant',
                'stock_disponible' => $produit->quantite_stock
            ], 400);
        }

        $panier = $this->getOrCreatePanier();

        $article = ArticlePanier::where('panier_id', $panier->id)
            ->where('produit_id', $produit->id)
            ->first();

        if ($article) {
            $nouvelleQuantite = $article->quantite + $request->quantite;
            
            if ($nouvelleQuantite > $produit->quantite_stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant',
                    'stock_disponible' => $produit->quantite_stock
                ], 400);
            }
            
            $article->update([
                'quantite' => $nouvelleQuantite,
                'prix_unitaire' => $produit->getPrixFinal(),
            ]);
        } else {
            ArticlePanier::create([
                'panier_id' => $panier->id,
                'produit_id' => $produit->id,
                'quantite' => $request->quantite,
                'prix_unitaire' => $produit->getPrixFinal(),
            ]);
        }

        $this->loadCartRelations($panier);

        return response()->json([
            'success' => true,
            'message' => 'Produit ajouté au panier',
            'data' => $this->formatCart($panier)
        ]);
    }

    /**
     * Mettre à jour la quantité
     */
    public function updateQuantity(Request $request, $articleId)
    {
        $validator = Validator::make($request->all(), [
            'quantite' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $article = ArticlePanier::findOrFail($articleId);
        $produit = $article->produit;

        if ($request->quantite > $produit->quantite_stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant',
                'stock_disponible' => $produit->quantite_stock
            ], 400);
        }

        $article->update([
            'quantite' => $request->quantite,
            'prix_unitaire' => $produit->getPrixFinal(),
        ]);

        $panier = $article->panier;
        $this->loadCartRelations($panier);

        return response()->json([
            'success' => true,
            'message' => 'Quantité mise à jour',
            'data' => $this->formatCart($panier)
        ]);
    }

    /**
     * Supprimer un article
     */
    public function remove($articleId)
    {
        $article = ArticlePanier::findOrFail($articleId);
        $panier = $article->panier;
        
        $article->delete();

        $this->loadCartRelations($panier);

        return response()->json([
            'success' => true,
            'message' => 'Article retiré du panier',
            'data' => $this->formatCart($panier)
        ]);
    }

    /**
     * Vider le panier
     */
    public function clear()
    {
        $panier = $this->getOrCreatePanier();
        $panier->articles()->delete();

        $this->loadCartRelations($panier);

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé',
            'data' => $this->formatCart($panier)
        ]);
    }

    /**
     * Appliquer un code promo (version simplifiée sans procédure stockée)
     */
    public function applyPromoCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Code requis'
            ], 422);
        }

        $panier = $this->getOrCreatePanier();
        $montantPanier = $panier->articles->sum(fn($a) => $a->quantite * $a->prix_unitaire);

        // Chercher le code promo
        $codePromo = CodePromo::where('code', $request->code)->first();

        if (!$codePromo || !$codePromo->estValide()) {
            return response()->json([
                'success' => false,
                'message' => 'Code promo invalide ou expiré',
                'reduction' => 0
            ]);
        }

        // Vérifier le montant minimum
        if ($montantPanier < $codePromo->montant_minimum) {
            return response()->json([
                'success' => false,
                'message' => 'Montant minimum requis: ' . $codePromo->montant_minimum . '€',
                'reduction' => 0
            ]);
        }

        // Vérifier l'utilisation par utilisateur
        $utilisationsUser = UtilisationCodePromo::where('code_promo_id', $codePromo->id)
            ->where('utilisateur_id', Auth::id())
            ->count();

        if ($codePromo->utilisation_par_user > 0 && $utilisationsUser >= $codePromo->utilisation_par_user) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà utilisé ce code le nombre maximum de fois',
                'reduction' => 0
            ]);
        }

        // Calculer la réduction
        $reduction = $codePromo->calculerReduction($montantPanier);

        // Stocker en session
        session(['promo_code' => $request->code, 'promo_reduction' => $reduction]);

        return response()->json([
            'success' => true,
            'message' => 'Code appliqué avec succès',
            'reduction' => $reduction,
            'data' => $this->formatCart($panier)
        ]);
    }

    /**
     * Retirer le code promo
     */
    public function removePromoCode()
    {
        session()->forget(['promo_code', 'promo_reduction']);

        $panier = $this->getOrCreatePanier();

        return response()->json([
            'success' => true,
            'message' => 'Code promo retiré',
            'data' => $this->formatCart($panier)
        ]);
    }

    /**
     * Obtenir ou créer le panier
     */
    private function getOrCreatePanier()
    {
        $panier = Panier::where('utilisateur_id', Auth::id())
            ->where('statut', 'actif')
            ->first();

        if (!$panier) {
            $panier = Panier::create([
                'utilisateur_id' => Auth::id(),
                'statut' => 'actif',
            ]);
        }

        return $panier;
    }

    private function loadCartRelations(Panier $panier): void
    {
        $panier->loadMissing([
            'articles.produit.categorie',
            'articles.produit.marque',
            'articles.produit.imagePrincipale',
        ]);
    }

    /**
     * Formater les données du panier
     */
    private function formatCart($panier)
    {
        $this->loadCartRelations($panier);

        $articles = $panier->articles->values()->map(function($article) {
            $produit = $article->produit;
            if (!$produit) {
                return null;
            }

            return [
                'id' => $article->id,
                'produit' => [
                    'id' => $produit->id,
                    'nom' => $produit->nom,
                    'slug' => $produit->slug,
                    'image' => $produit->image_display_url,
                    'categorie' => $produit->categorie?->nom,
                    'marque' => $produit->marque?->nom,
                ],
                'quantite' => $article->quantite,
                'prix_unitaire' => (float) $article->prix_unitaire,
                'prix_total' => (float) ($article->quantite * $article->prix_unitaire),
                'en_stock' => $produit->quantite_stock >= $article->quantite,
            ];
        })->filter()->values();

        $sousTotal = $articles->sum('prix_total');
        $reduction = session('promo_reduction', 0);
        $fraisLivraison = $sousTotal >= 100 ? 0 : 9.99;
        $total = $sousTotal - $reduction + $fraisLivraison;

        return [
            'id' => $panier->id,
            'articles' => $articles,
            'nombre_articles' => $articles->sum('quantite'),
            'sous_total' => (float) $sousTotal,
            'reduction' => (float) $reduction,
            'code_promo' => session('promo_code'),
            'frais_livraison' => (float) $fraisLivraison,
            'total' => (float) $total,
            'livraison_gratuite' => $sousTotal >= 100,
        ];
    }
}