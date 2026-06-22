<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $totalCommandes = Commande::count();
            $totalProduits = Produit::count();
            $totalClients = User::where('role', 'client')->count();
            $totalAdmins = User::whereIn('role', ['administrateur', 'super_administrateur'])->count();
            
            $revenuTotal = Commande::where('statut_paiement', 'paye')->sum('montant_total');
            
            $commandesEnAttente = Commande::where('statut', 'en_attente')->count();
            $commandesLivrees = Commande::where('statut', 'livree')->count();
            
            $produitsEnStock = Produit::where('quantite_stock', '>', 0)->count();
            $produitsStockFaible = Produit::whereBetween('quantite_stock', [1, 10])->count();
            $produitsEnRupture = Produit::where('quantite_stock', 0)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_commandes' => (int) $totalCommandes,
                    'total_produits' => (int) $totalProduits,
                    'total_clients' => (int) $totalClients,
                    'total_admins' => (int) $totalAdmins,
                    'revenu_total' => (float) $revenuTotal,
                    'commandes_en_attente' => (int) $commandesEnAttente,
                    'commandes_livrees' => (int) $commandesLivrees,
                    'produits_en_stock' => (int) $produitsEnStock,
                    'produits_stock_faible' => (int) $produitsStockFaible,
                    'produits_en_rupture' => (int) $produitsEnRupture,
                    'timestamp' => time(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('DashboardController error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}