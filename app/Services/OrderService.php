<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Panier;
use App\Models\ArticleCommande;
use App\Models\AdresseLivraison;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Créer une commande complète
     */
    public function creerCommande($userId, $adresseId, $methodePaiement, $noteClient = null)
    {
        return DB::transaction(function () use ($userId, $adresseId, $methodePaiement, $noteClient) {
            $panier = Panier::where('utilisateur_id', $userId)
                ->where('statut', 'actif')
                ->with('articles.produit')
                ->first();

            if (!$panier || $panier->articles->isEmpty()) {
                throw new \Exception('Panier vide');
            }

            // Vérifier le stock
            foreach ($panier->articles as $article) {
                if ($article->produit->quantite_stock < $article->quantite) {
                    throw new \Exception("Stock insuffisant pour {$article->produit->nom}");
                }
            }

            $sousTotal = $panier->articles->sum(fn($a) => $a->quantite * $a->prix_unitaire);
            $fraisLivraison = $sousTotal >= 100 ? 0 : 9.99;
            $total = $sousTotal + $fraisLivraison;

            $commande = Commande::create([
                'utilisateur_id' => $userId,
                'numero_commande' => Commande::genererNumeroCommande(),
                'montant_total' => $total,
                'frais_livraison' => $fraisLivraison,
                'statut' => 'en_attente',
                'adresse_livraison_id' => $adresseId,
                'note_client' => $noteClient,
            ]);

            foreach ($panier->articles as $article) {
                ArticleCommande::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $article->produit_id,
                    'produit_nom' => $article->produit->nom,
                    'quantite' => $article->quantite,
                    'prix' => $article->prix_unitaire,
                    'prix_total' => $article->quantite * $article->prix_unitaire,
                ]);
            }

            $panier->update(['statut' => 'converti']);

            return $commande;
        });
    }

    /**
     * Annuler une commande
     */
    public function annulerCommande($commandeId, $userId, $raison = null)
    {
        $commande = Commande::where('id', $commandeId)
            ->where('utilisateur_id', $userId)
            ->firstOrFail();

        if (!in_array($commande->statut, ['en_attente', 'payee'])) {
            throw new \Exception('Commande non annulable');
        }

        $commande->changerStatut('annulee', $userId, $raison);

        Notification::create([
            'utilisateur_id' => $commande->utilisateur_id,
            'titre' => 'Commande annulée',
            'message' => "Votre commande {$commande->numero_commande} a été annulée.",
            'type' => 'commande',
        ]);

        return $commande;
    }

    /**
     * Statistiques commandes
     */
    public function getStats($periode = '30')
    {
        $dateDebut = now()->subDays($periode);

        return [
            'total_commandes' => Commande::where('created_at', '>=', $dateDebut)->count(),
            'revenu_total' => Commande::where('created_at', '>=', $dateDebut)
                ->where('statut', '!=', 'annulee')
                ->sum('montant_total'),
            'panier_moyen' => Commande::where('created_at', '>=', $dateDebut)
                ->where('statut', '!=', 'annulee')
                ->avg('montant_total'),
            'commandes_par_statut' => Commande::where('created_at', '>=', $dateDebut)
                ->selectRaw('statut, COUNT(*) as count')
                ->groupBy('statut')
                ->get()
                ->pluck('count', 'statut'),
        ];
    }
}