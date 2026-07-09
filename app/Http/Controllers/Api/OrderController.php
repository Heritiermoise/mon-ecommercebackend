<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderInvoiceMail;
use App\Models\ArticleCommande;
use App\Models\Commande;
use App\Models\Panier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Créer une nouvelle commande
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'adresse_livraison_id' => 'required|exists:adresses_livraison,id',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();

            $panier = Panier::where('utilisateur_id', $user->id)
                ->where('statut', 'actif')
                ->with(['articles.produit'])
                ->first();

            if (!$panier || $panier->articles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre panier est vide',
                ], 400);
            }

            $montantTotal = 0;
            $articlesData = [];

            foreach ($panier->articles as $article) {
                $produit = $article->produit;
                if (!$produit) {
                    continue;
                }

                $prixUnitaire = (float) $article->prix_unitaire;
                $prixTotal = $prixUnitaire * $article->quantite;
                $montantTotal += $prixTotal;

                $articlesData[] = [
                    'produit_id' => $produit->id,
                    'produit_nom' => $produit->nom,
                    'quantite' => $article->quantite,
                    'prix' => $prixUnitaire,
                    'prix_total' => $prixTotal,
                ];
            }

            $fraisLivraison = $montantTotal >= 100 ? 0 : 9.99;
            $reduction = 0;
            $totalFinal = $montantTotal + $fraisLivraison - $reduction;
            $numeroCommande = 'CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $commande = Commande::create([
                'numero_commande' => $numeroCommande,
                'utilisateur_id' => $user->id,
                'adresse_livraison_id' => $request->adresse_livraison_id,
                'montant_total' => $montantTotal,
                'frais_livraison' => $fraisLivraison,
                'reduction' => $reduction,
                'statut' => 'en_attente',
                'statut_paiement' => 'non_paye',
                'note_client' => $request->notes,
            ]);

            foreach ($articlesData as $articleData) {
                ArticleCommande::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $articleData['produit_id'],
                    'produit_nom' => $articleData['produit_nom'],
                    'quantite' => $articleData['quantite'],
                    'prix' => $articleData['prix'],
                    'prix_total' => $articleData['prix_total'],
                ]);
            }

            $panier->articles()->delete();
            DB::commit();

            try {
                Mail::to($user->email)->send(new OrderConfirmationMail($commande));
                if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    Mail::to($user->email)->send(new OrderInvoiceMail($commande));
                }
                Log::info('Emails de commande envoyés', ['commande' => $numeroCommande]);
            } catch (\Exception $e) {
                Log::error('Erreur envoi email confirmation: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande creee avec succes',
                'data' => [
                    'id' => $commande->id,
                    'numero_commande' => $commande->numero_commande,
                    'montant_total' => (float) $commande->montant_total,
                    'frais_livraison' => (float) $commande->frais_livraison,
                    'reduction' => (float) $commande->reduction,
                    'total_final' => (float) $totalFinal,
                    'statut' => $commande->statut,
                    'statut_paiement' => $commande->statut_paiement,
                    'nombre_articles' => count($articlesData),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('OrderController@store: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lister les commandes de l'utilisateur connecté
     */
    public function userOrders()
    {
        try {
            $commandes = Commande::where('utilisateur_id', Auth::id())
                ->with(['articles.produit', 'adresseLivraison', 'paiement'])
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $commandes->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'numero_commande' => $c->numero_commande,
                        'montant_total' => (float) $c->montant_total,
                        'frais_livraison' => (float) $c->frais_livraison,
                        'reduction' => (float) $c->reduction,
                        'total_final' => (float) $c->montant_total + (float) $c->frais_livraison - (float) $c->reduction,
                        'statut' => $c->statut,
                        'statut_paiement' => $c->statut_paiement,
                        'methode_paiement' => $c->paiement ? $c->paiement->methode : null,
                        'date_creation' => $c->created_at ? $c->created_at->format('d/m/Y H:i') : '',
                        'date_paiement' => $c->paiement && $c->paiement->paye_le ? $c->paiement->paye_le->format('d/m/Y H:i') : null,
                        'nombre_articles' => $c->articles ? $c->articles->count() : 0,
                        'articles' => $c->articles ? $c->articles->values()->map(function ($a) {
                            return [
                                'id' => $a->id,
                                'produit_nom' => $a->produit_nom,
                                'quantite' => (int) $a->quantite,
                                'prix' => (float) $a->prix,
                                'prix_total' => (float) $a->prix_total,
                            ];
                        })->values() : [],
                        'adresse_livraison' => $c->adresseLivraison ? [
                            'nom_complet' => $c->adresseLivraison->nom_complet,
                            'telephone' => $c->adresseLivraison->telephone,
                            'adresse' => $c->adresseLivraison->adresse,
                            'ville' => $c->adresseLivraison->ville,
                        ] : null,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('OrderController@userOrders: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher une commande spécifique
     */
    public function show($numero)
    {
        try {
            $commande = Commande::where('numero_commande', $numero)
                ->where('utilisateur_id', Auth::id())
                ->with(['articles.produit', 'adresseLivraison', 'paiement'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $commande->id,
                    'numero_commande' => $commande->numero_commande,
                    'montant_total' => (float) $commande->montant_total,
                    'frais_livraison' => (float) $commande->frais_livraison,
                    'reduction' => (float) $commande->reduction,
                    'total_final' => (float) $commande->montant_total + (float) $commande->frais_livraison - (float) $commande->reduction,
                    'statut' => $commande->statut,
                    'statut_paiement' => $commande->statut_paiement,
                    'methode_paiement' => $commande->paiement ? $commande->paiement->methode : null,
                    'date_creation' => $commande->created_at ? $commande->created_at->format('d/m/Y H:i') : '',
                    'date_paiement' => $commande->paiement && $commande->paiement->paye_le ? $commande->paiement->paye_le->format('d/m/Y H:i') : null,
                    'articles' => $commande->articles->values()->map(function ($a) {
                        return [
                            'id' => $a->id,
                            'produit_id' => $a->produit_id,
                            'produit_nom' => $a->produit_nom,
                            'quantite' => (int) $a->quantite,
                            'prix' => (float) $a->prix,
                            'prix_total' => (float) $a->prix_total,
                        ];
                    }),
                    'adresse_livraison' => $commande->adresseLivraison,
                    'paiement' => $commande->paiement,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Annuler une commande
     */
    public function cancel($numero)
    {
        try {
            $commande = Commande::where('numero_commande', $numero)
                ->where('utilisateur_id', Auth::id())
                ->firstOrFail();

            if ($commande->statut_paiement === 'paye') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d annuler une commande deja payee',
                ], 400);
            }

            $commande->update([
                'statut' => 'annulee',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande annulee',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Lister toutes les commandes
     */
    public function adminOrders(Request $request)
    {
        try {
            $query = Commande::with(['utilisateur', 'articles.produit', 'adresseLivraison', 'paiement']);

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('statut_paiement')) {
                $query->where('statut_paiement', $request->statut_paiement);
            }

            $commandes = $query->orderByDesc('created_at')->paginate(20);
            $formatted = $commandes->getCollection()->map(function ($c) {
                return [
                    'id' => $c->id,
                    'numero_commande' => $c->numero_commande,
                    'utilisateur' => $c->utilisateur ? [
                        'id' => $c->utilisateur->id,
                        'nom' => $c->utilisateur->nom,
                        'email' => $c->utilisateur->email,
                    ] : null,
                    'montant_total' => (float) $c->montant_total,
                    'total_final' => (float) $c->montant_total + (float) $c->frais_livraison - (float) $c->reduction,
                    'statut' => $c->statut,
                    'statut_paiement' => $c->statut_paiement,
                    'methode_paiement' => $c->paiement ? $c->paiement->methode : null,
                    'date_creation' => $c->created_at ? $c->created_at->format('d/m/Y H:i') : '',
                    'nombre_articles' => $c->articles ? $c->articles->count() : 0,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formatted,
                'pagination' => [
                    'total' => $commandes->total(),
                    'per_page' => $commandes->perPage(),
                    'current_page' => $commandes->currentPage(),
                    'last_page' => $commandes->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Changer le statut d'une commande
     */
    public function changeStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'statut' => 'required|in:en_attente,confirmee,expediee,livree,annulee',
            ]);

            $commande = Commande::findOrFail($id);
            $commande->update(['statut' => $request->statut]);

            return response()->json([
                'success' => true,
                'message' => 'Statut mis a jour',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
