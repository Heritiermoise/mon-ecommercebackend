<?php

namespace App\Services;

use App\Models\Produit;
use App\Models\MouvementStock;
use App\Models\Categorie;
use App\Models\Marque;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StockService
{
    /**
     * Ajouter du stock de manière intelligente
     * Vérifie si un produit identique existe déjà
     */
    public static function ajouterStock(array $data, $userId = null): array
    {
        DB::beginTransaction();

        try {
            // Normaliser les données pour la comparaison
            $nom = trim(strtolower($data['nom']));
            $categorieId = $data['categorie_id'] ?? null;
            $marqueId = $data['marque_id'] ?? null;
            $nouvelleQuantite = (int) ($data['quantite_stock'] ?? 0);

            // Rechercher un produit identique
            $produitExistant = self::trouverProduitIdentique($nom, $categorieId, $marqueId);

            if ($produitExistant) {
                // PRODUIT EXISTANT : Ajouter la quantité
                $stockAvant = $produitExistant->quantite_stock;
                $stockApres = $stockAvant + $nouvelleQuantite;

                $produitExistant->update([
                    'quantite_stock' => $stockApres,
                ]);

                // Enregistrer le mouvement
                self::enregistrerMouvement([
                    'produit_id' => $produitExistant->id,
                    'utilisateur_id' => $userId,
                    'type' => 'entree',
                    'quantite' => $nouvelleQuantite,
                    'stock_avant' => $stockAvant,
                    'stock_apres' => $stockApres,
                    'reference' => 'REAPPRO-' . strtoupper(Str::random(8)),
                    'note' => "Réapprovisionnement de $nouvelleQuantite unité(s)",
                ]);

                DB::commit();

                Log::info('Stock ajouté au produit existant', [
                    'produit_id' => $produitExistant->id,
                    'quantite' => $nouvelleQuantite,
                    'stock_avant' => $stockAvant,
                    'stock_apres' => $stockApres,
                ]);

                return [
                    'success' => true,
                    'message' => "Stock ajouté au produit existant : {$produitExistant->nom}",
                    'action' => 'updated',
                    'produit' => $produitExistant->fresh(),
                    'stock_avant' => $stockAvant,
                    'stock_apres' => $stockApres,
                ];
            } else {
                // NOUVEAU PRODUIT : Créer le produit
                $produit = Produit::create([
                    'nom' => $data['nom'],
                    'slug' => Str::slug($data['nom']) . '-' . uniqid(),
                    'description' => $data['description'] ?? null,
                    'prix' => $data['prix'] ?? 0,
                    'prix_remise' => $data['prix_remise'] ?? null,
                    'quantite_stock' => $nouvelleQuantite,
                    'categorie_id' => $categorieId,
                    'marque_id' => $marqueId,
                    'statut' => $data['statut'] ?? 'actif',
                ]);

                // Enregistrer le mouvement initial
                self::enregistrerMouvement([
                    'produit_id' => $produit->id,
                    'utilisateur_id' => $userId,
                    'type' => 'entree',
                    'quantite' => $nouvelleQuantite,
                    'stock_avant' => 0,
                    'stock_apres' => $nouvelleQuantite,
                    'reference' => 'INIT-' . strtoupper(Str::random(8)),
                    'note' => 'Création du produit avec stock initial',
                ]);

                DB::commit();

                Log::info('Nouveau produit créé', [
                    'produit_id' => $produit->id,
                    'nom' => $produit->nom,
                    'stock_initial' => $nouvelleQuantite,
                ]);

                return [
                    'success' => true,
                    'message' => "Nouveau produit créé : {$produit->nom}",
                    'action' => 'created',
                    'produit' => $produit,
                    'stock_avant' => 0,
                    'stock_apres' => $nouvelleQuantite,
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur StockService::ajouterStock: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Trouver un produit identique (comparaison intelligente)
     */
    private static function trouverProduitIdentique(string $nom, ?int $categorieId, ?int $marqueId): ?Produit
    {
        $query = Produit::query();

        // Comparer le nom en ignorant les espaces et la casse
        $query->whereRaw('LOWER(TRIM(nom)) = ?', [strtolower(trim($nom))]);

        // Si catégorie spécifiée, filtrer par catégorie
        if ($categorieId) {
            $query->where('categorie_id', $categorieId);
        }

        // Si marque spécifiée, filtrer par marque
        if ($marqueId) {
            $query->where('marque_id', $marqueId);
        }

        return $query->first();
    }

    /**
     * Enregistrer un mouvement de stock
     */
    public static function enregistrerMouvement(array $data): MouvementStock
    {
        return MouvementStock::create([
            'produit_id' => $data['produit_id'],
            'utilisateur_id' => $data['utilisateur_id'] ?? null,
            'type' => $data['type'],
            'quantite' => $data['quantite'],
            'stock_avant' => $data['stock_avant'],
            'stock_apres' => $data['stock_apres'],
            'reference' => $data['reference'] ?? null,
            'note' => $data['note'] ?? null,
        ]);
    }

    /**
     * Ajuster le stock (ajustement manuel)
     */
    public static function ajusterStock(int $produitId, int $nouveauStock, ?int $userId = null, ?string $note = null): array
    {
        DB::beginTransaction();

        try {
            $produit = Produit::findOrFail($produitId);
            $stockAvant = $produit->quantite_stock;

            $produit->update(['quantite_stock' => $nouveauStock]);

            self::enregistrerMouvement([
                'produit_id' => $produitId,
                'utilisateur_id' => $userId,
                'type' => 'ajustement',
                'quantite' => $nouveauStock - $stockAvant,
                'stock_avant' => $stockAvant,
                'stock_apres' => $nouveauStock,
                'reference' => 'ADJ-' . strtoupper(Str::random(8)),
                'note' => $note ?? 'Ajustement manuel du stock',
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Stock ajusté',
                'produit' => $produit->fresh(),
                'stock_avant' => $stockAvant,
                'stock_apres' => $nouveauStock,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enregistrer une sortie de stock (vente)
     */
    public static function enregistrerVente(int $produitId, int $quantite, ?int $userId = null, ?string $reference = null): array
    {
        DB::beginTransaction();

        try {
            $produit = Produit::findOrFail($produitId);
            $stockAvant = $produit->quantite_stock;

            if ($stockAvant < $quantite) {
                throw new \Exception('Stock insuffisant');
            }

            $stockApres = $stockAvant - $quantite;
            $produit->update(['quantite_stock' => $stockApres]);

            self::enregistrerMouvement([
                'produit_id' => $produitId,
                'utilisateur_id' => $userId,
                'type' => 'vente',
                'quantite' => -$quantite,
                'stock_avant' => $stockAvant,
                'stock_apres' => $stockApres,
                'reference' => $reference ?? 'VENTE-' . strtoupper(Str::random(8)),
                'note' => "Vente de $quantite unité(s)",
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Vente enregistrée',
                'produit' => $produit->fresh(),
                'stock_avant' => $stockAvant,
                'stock_apres' => $stockApres,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtenir l'historique des mouvements d'un produit
     */
    public static function getHistoriqueProduit(int $produitId, int $limit = 50): array
    {
        return MouvementStock::where('produit_id', $produitId)
            ->with('utilisateur')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function($m) {
                return [
                    'id' => $m->id,
                    'type' => $m->type,
                    'quantite' => $m->quantite,
                    'stock_avant' => $m->stock_avant,
                    'stock_apres' => $m->stock_apres,
                    'reference' => $m->reference,
                    'note' => $m->note,
                    'utilisateur' => $m->utilisateur ? $m->utilisateur->nom : 'Système',
                    'date' => $m->created_at->format('d/m/Y H:i'),
                ];
            })
            ->toArray();
    }

    /**
     * Obtenir les statistiques de stock
     */
    public static function getStatistiques(): array
    {
        $totalProduits = Produit::count();
        $enStock = Produit::where('quantite_stock', '>', 0)->count();
        $rupture = Produit::where('quantite_stock', 0)->count();
        $stockFaible = Produit::whereBetween('quantite_stock', [1, 10])->count();

        $entrees30j = MouvementStock::where('type', 'entree')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('quantite');

        $sorties30j = abs(MouvementStock::where('type', 'vente')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('quantite'));

        return [
            'total_produits' => $totalProduits,
            'en_stock' => $enStock,
            'en_rupture' => $rupture,
            'stock_faible' => $stockFaible,
            'entrees_30j' => (int) $entrees30j,
            'sorties_30j' => (int) $sorties30j,
        ];
    }
}