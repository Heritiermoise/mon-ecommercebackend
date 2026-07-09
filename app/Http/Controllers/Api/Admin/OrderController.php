<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Commande::with(['utilisateur', 'adresseLivraison', 'articles', 'paiement']);

            if ($request->has('statut') && $request->statut) {
                $query->where('statut', $request->statut);
            }

            if ($request->has('statut_paiement') && $request->statut_paiement) {
                $query->where('statut_paiement', $request->statut_paiement);
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where('numero_commande', 'like', '%' . $search . '%');
            }

            $commandes = $query->latest()->paginate(20);

            $formatted = $commandes->getCollection()->map(function($c) {
                return [
                    'id' => (int) $c->id,
                    'numero_commande' => $c->numero_commande,
                    'utilisateur' => $c->utilisateur ? [
                        'id' => (int) $c->utilisateur->id,
                        'nom' => $c->utilisateur->nom,
                        'email' => $c->utilisateur->email,
                        'telephone' => $c->utilisateur->telephone,
                    ] : null,
                    'montant_total' => (float) $c->montant_total,
                    'frais_livraison' => (float) $c->frais_livraison,
                    'reduction' => (float) $c->reduction,
                    'total_final' => (float) $c->montant_total + (float) $c->frais_livraison - (float) $c->reduction,
                    'statut' => $c->statut,
                    'statut_paiement' => $c->statut_paiement,
                    'methode_paiement' => $c->paiement ? $c->paiement->methode : null,
                    'nombre_articles' => $c->articles ? $c->articles->count() : 0,
                    'date_creation' => $c->created_at ? $c->created_at->format('d/m/Y H:i') : '',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formatted,
                'pagination' => [
                    'total' => (int) $commandes->total(),
                    'per_page' => (int) $commandes->perPage(),
                    'current_page' => (int) $commandes->currentPage(),
                    'last_page' => (int) $commandes->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Admin OrderController error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($numeroCommande)
    {
        try {
            $commande = Commande::where('numero_commande', $numeroCommande)
                ->with(['utilisateur', 'adresseLivraison', 'articles.produit', 'paiement'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $commande
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function changeStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'statut' => 'required|string',
            ]);

            $commande = Commande::findOrFail($id);
            $commande->update(['statut' => $request->statut]);

            return response()->json([
                'success' => true,
                'message' => 'Statut mis a jour',
                'data' => $commande
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}