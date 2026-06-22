<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Produit;
use App\Models\User;
use App\Models\Paiement;
use App\Models\Avis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatisticsController extends Controller
{
    public function index()
    {
        return $this->dashboard();
    }

    public function dashboard()
    {
        try {
            $totalRevenu = Commande::where('statut_paiement', 'paye')->sum('montant_total');
            $totalCommandes = Commande::count();
            $commandesPayees = Commande::where('statut_paiement', 'paye')->count();
            $commandesEnAttente = Commande::where('statut', 'en_attente')->count();
            $commandesLivrees = Commande::where('statut', 'livree')->count();
            $totalClients = User::where('role', 'client')->count();
            $totalProduits = Produit::count();
            $produitsEnStock = Produit::where('quantite_stock', '>', 0)->count();
            $produitsRupture = Produit::where('quantite_stock', 0)->count();
            $panierMoyen = $totalCommandes > 0 ? $totalRevenu / $totalCommandes : 0;
            $noteMoyenne = Avis::where('est_approuve', true)->avg('note') ?? 0;
            $totalAvis = Avis::where('est_approuve', true)->count();

            $revenusParMois = Commande::where('statut_paiement', 'paye')
                ->where('created_at', '>=', now()->subMonths(12))
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mois'),
                    DB::raw('SUM(montant_total) as total'),
                    DB::raw('COUNT(*) as nombre')
                )
                ->groupBy('mois')
                ->orderBy('mois')
                ->get()
                ->map(function($item) {
                    return [
                        'mois' => $item->mois,
                        'total' => (float) $item->total,
                        'nombre' => (int) $item->nombre,
                    ];
                });

            $commandesParJour = Commande::where('created_at', '>=', now()->subDays(30))
                ->select(
                    DB::raw('DATE(created_at) as jour'),
                    DB::raw('COUNT(*) as nombre'),
                    DB::raw('SUM(CASE WHEN statut_paiement = "paye" THEN montant_total ELSE 0 END) as revenu')
                )
                ->groupBy('jour')
                ->orderBy('jour')
                ->get()
                ->map(function($item) {
                    return [
                        'jour' => $item->jour,
                        'nombre' => (int) $item->nombre,
                        'revenu' => (float) $item->revenu,
                    ];
                });

            $topProduits = DB::table('articles_commande')
                ->join('produits', 'articles_commande.produit_id', '=', 'produits.id')
                ->select(
                    'produits.id',
                    'produits.nom',
                    DB::raw('SUM(articles_commande.quantite) as total_vendu'),
                    DB::raw('SUM(articles_commande.prix_total) as revenu_total')
                )
                ->groupBy('produits.id', 'produits.nom')
                ->orderByDesc('total_vendu')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'nom' => $item->nom,
                        'total_vendu' => (int) $item->total_vendu,
                        'revenu_total' => (float) $item->revenu_total,
                    ];
                });

            $ventesParCategorie = DB::table('articles_commande')
                ->join('produits', 'articles_commande.produit_id', '=', 'produits.id')
                ->join('categories', 'produits.categorie_id', '=', 'categories.id')
                ->select(
                    'categories.nom',
                    DB::raw('COUNT(*) as nombre_ventes'),
                    DB::raw('SUM(articles_commande.prix_total) as total_revenu')
                )
                ->groupBy('categories.nom')
                ->orderByDesc('total_revenu')
                ->get()
                ->map(function($item) {
                    return [
                        'nom' => $item->nom,
                        'nombre_ventes' => (int) $item->nombre_ventes,
                        'total_revenu' => (float) $item->total_revenu,
                    ];
                });

            $distributionNotes = Avis::where('est_approuve', true)
                ->select('note', DB::raw('COUNT(*) as count'))
                ->groupBy('note')
                ->orderBy('note')
                ->get()
                ->map(function($item) {
                    return [
                        'note' => (int) $item->note,
                        'count' => (int) $item->count,
                    ];
                });

            $methodesPaiement = Paiement::where('statut', 'paye')
                ->select('methode', DB::raw('COUNT(*) as nombre'), DB::raw('SUM(montant) as total'))
                ->groupBy('methode')
                ->get()
                ->map(function($item) {
                    return [
                        'methode' => $item->methode ?? 'inconnu',
                        'nombre' => (int) $item->nombre,
                        'total' => (float) $item->total,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'globales' => [
                        'revenu_total' => (float) $totalRevenu,
                        'total_commandes' => (int) $totalCommandes,
                        'commandes_payees' => (int) $commandesPayees,
                        'commandes_en_attente' => (int) $commandesEnAttente,
                        'commandes_livrees' => (int) $commandesLivrees,
                        'total_clients' => (int) $totalClients,
                        'total_produits' => (int) $totalProduits,
                        'produits_en_stock' => (int) $produitsEnStock,
                        'produits_en_rupture' => (int) $produitsRupture,
                        'panier_moyen' => (float) $panierMoyen,
                        'note_moyenne' => (float) $noteMoyenne,
                        'total_avis' => (int) $totalAvis,
                    ],
                    'revenus_par_mois' => $revenusParMois,
                    'commandes_par_jour' => $commandesParJour,
                    'top_produits' => $topProduits,
                    'ventes_par_categorie' => $ventesParCategorie,
                    'distribution_notes' => $distributionNotes,
                    'methodes_paiement' => $methodesPaiement,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('StatisticsController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}