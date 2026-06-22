<?php

namespace App\Services;

use App\Models\User;
use App\Models\PointFidelite;
use App\Models\RecompenseFidelite;  // ✅ IMPORT AJOUTÉ

class LoyaltyService
{
    /**
     * Calculer les points d'un utilisateur
     */
    public function calculerPoints($userId)
    {
        $gagnes = PointFidelite::where('utilisateur_id', $userId)
            ->where('type', 'gain')
            ->sum('points_montant');

        $utilises = PointFidelite::where('utilisateur_id', $userId)
            ->where('type', 'utilisation')
            ->sum('points_montant');

        return [
            'total' => $gagnes - $utilises,
            'gagnes' => $gagnes,
            'utilises' => $utilises,
        ];
    }

    /**
     * Ajouter des points
     */
    public function ajouterPoints($userId, $montant, $commandeId = null, $description = null)
    {
        $points = floor($montant); // 1 point par euro

        return PointFidelite::create([
            'utilisateur_id' => $userId,
            'points' => $points,
            'type' => 'gain',
            'description' => $description ?? "Points gagnés",
            'points_montant' => $points,
            'commande_id' => $commandeId,
        ]);
    }

    /**
     * Utiliser des points
     */
    public function utiliserPoints($userId, $points, $description = null)
    {
        $solde = $this->calculerPoints($userId)['total'];

        if ($solde < $points) {
            throw new \Exception('Solde de points insuffisant');
        }

        return PointFidelite::create([
            'utilisateur_id' => $userId,
            'points' => -$points,
            'type' => 'utilisation',
            'description' => $description ?? 'Utilisation de points',
            'points_montant' => $points,
        ]);
    }

    /**
     * Liste des récompenses disponibles
     */
    public function recompensesDisponibles($userId)
    {
        $points = $this->calculerPoints($userId)['total'];

        return RecompenseFidelite::where('statut', 'actif')
            ->where('stock_disponible', '>', 0)
            ->where('points_necessaires', '<=', $points)
            ->get();  // ✅ POINT-VIRGULE AJOUTÉ
    }

    /**
     * Échanger des points contre une récompense
     */
    public function echangerPoints($userId, $recompenseId)
    {
        $recompense = RecompenseFidelite::findOrFail($recompenseId);

        // Vérifier disponibilité
        if (!$recompense->estDisponible()) {
            throw new \Exception('Cette récompense n\'est plus disponible');
        }

        $points = $this->calculerPoints($userId)['total'];

        // Vérifier points suffisants
        if (!$recompense->utilisateurAssezDePoints($points)) {
            throw new \Exception('Points insuffisants pour cette récompense');
        }

        // Décrémenter les points
        $this->utiliserPoints(
            $userId, 
            $recompense->points_necessaires, 
            'Échange récompense: ' . $recompense->nom
        );

        // Décrémenter le stock
        $recompense->decrementerStock();

        return [
            'success' => true,
            'message' => 'Récompense échangée avec succès',
            'recompense' => $recompense->nom,
            'points_utilises' => $recompense->points_necessaires,
        ];
    }

    /**
     * Historique des points d'un utilisateur
     */
    public function historiquePoints($userId, $limit = 20)
    {
        return PointFidelite::where('utilisateur_id', $userId)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'type' => $p->type,
                'points' => $p->points_montant,
                'description' => $p->description,
                'commande_id' => $p->commande_id,
                'date' => $p->created_at->toISOString(),
            ]);
    }
}