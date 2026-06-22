<?php

namespace App\Services;

use App\Models\Wishlist;
use App\Models\WishlistAlerte;
use App\Models\WishlistPartagee;
use App\Models\Panier;
use App\Models\ArticlePanier;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WishlistService
{
    public static function ajouterAuWishlist($userId, $produitId, $options = [])
    {
        $produit = Produit::find($produitId);
        if (!$produit) {
            throw new \Exception('Produit non trouve');
        }

        $wishlist = Wishlist::ajouter($userId, $produitId, $options);

        return [
            'success' => true,
            'message' => 'Produit ajoute aux favoris',
            'data' => $wishlist,
        ];
    }

    public static function retirerDuWishlist($userId, $produitId)
    {
        Wishlist::supprimer($userId, $produitId);

        return [
            'success' => true,
            'message' => 'Produit retire des favoris',
        ];
    }

    public static function getWishlist($userId, $collection = null)
    {
        $query = Wishlist::where('utilisateur_id', $userId)
            ->with([
                'produit.categorie',
                'produit.marque',
                'produit.imagePrincipale',
                'alertes' => function($q) {
                    $q->orderByDesc('created_at')->limit(3);
                }
            ]);

        if ($collection) {
            $query->where('nom_collection', $collection);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public static function getCollections($userId)
    {
        return Wishlist::where('utilisateur_id', $userId)
            ->select('nom_collection', DB::raw('COUNT(*) as nb_produits'))
            ->groupBy('nom_collection')
            ->get();
    }

    public static function transfererAuPanier($userId, $produitIds = null)
    {
        $query = Wishlist::where('utilisateur_id', $userId);
        
        if ($produitIds) {
            $query->whereIn('produit_id', $produitIds);
        }

        $wishlists = $query->with('produit')->get();

        if ($wishlists->isEmpty()) {
            throw new \Exception('Aucun produit a transferer');
        }

        $panier = Panier::firstOrCreate(
            ['utilisateur_id' => $userId, 'statut' => 'actif'],
            ['date_creation' => now()]
        );

        $transferes = [];
        $echecs = [];

        foreach ($wishlists as $wishlist) {
            $produit = $wishlist->produit;
            if (!$produit || $produit->quantite_stock <= 0) {
                $echecs[] = $produit ? $produit->nom : 'Produit inconnu';
                continue;
            }

            $article = ArticlePanier::where('panier_id', $panier->id)
                ->where('produit_id', $produit->id)
                ->first();

            $prix = $produit->prix_remise ?? $produit->prix;

            if ($article) {
                $article->quantite += 1;
                $article->prix_unitaire = $prix;
                $article->save();
            } else {
                ArticlePanier::create([
                    'panier_id' => $panier->id,
                    'produit_id' => $produit->id,
                    'quantite' => 1,
                    'prix_unitaire' => $prix,
                ]);
            }

            $transferes[] = $produit->nom;
        }

        return [
            'success' => true,
            'message' => count($transferes) . ' produit(s) transferes au panier',
            'transferes' => $transferes,
            'echecs' => $echecs,
        ];
    }

    public static function creerPartage($userId, $nom = 'Ma wishlist', $expirationJours = null)
    {
        $partagee = WishlistPartagee::create([
            'utilisateur_id' => $userId,
            'nom' => $nom,
            'est_publique' => true,
            'expire_le' => $expirationJours ? now()->addDays($expirationJours) : null,
        ]);

        return [
            'success' => true,
            'token' => $partagee->token,
            'url' => url('/wishlist/partage/' . $partagee->token),
        ];
    }

    public static function getPartage($token)
    {
        $partagee = WishlistPartagee::getByToken($token);
        if (!$partagee) {
            throw new \Exception('Wishlist non trouvee ou expiree');
        }

        $partagee->incrementerVues();

        return [
            'nom' => $partagee->nom,
            'proprietaire' => $partagee->utilisateur->nom,
            'items' => $partagee->getWishlistItems()->map(function($w) {
                $p = $w->produit;
                return [
                    'id' => $p->id,
                    'nom' => $p->nom,
                    'slug' => $p->slug,
                    'prix' => (float) $p->prix,
                    'prix_remise' => $p->prix_remise ? (float) $p->prix_remise : null,
                    'image' => $p->imagePrincipale ? $p->imagePrincipale->url_image : null,
                    'categorie' => $p->categorie ? $p->categorie->nom : null,
                    'en_stock' => $p->quantite_stock > 0,
                ];
            }),
        ];
    }

    public static function verifierAlertesPrix()
    {
        $alertesATraiter = Wishlist::getAlertesEnAttente();
        $nbAlertes = 0;

        foreach ($alertesATraiter as $wishlist) {
            $produit = $wishlist->produit;
            $prixActuel = $produit->prix_remise ?? $produit->prix;

            if ($prixActuel < $wishlist->prix_ajout) {
                $pourcentage = round((($wishlist->prix_ajout - $prixActuel) / $wishlist->prix_ajout) * 100, 2);

                WishlistAlerte::create([
                    'wishlist_id' => $wishlist->id,
                    'ancien_prix' => $wishlist->prix_ajout,
                    'nouveau_prix' => $prixActuel,
                    'pourcentage_reduction' => $pourcentage,
                ]);

                $wishlist->update([
                    'prix_ajout' => $prixActuel,
                    'derniere_alerte' => now(),
                ]);

                self::envoyerEmailAlerte($wishlist, $produit, $prixActuel, $pourcentage);
                $nbAlertes++;
            }
        }

        return $nbAlertes;
    }

    private static function envoyerEmailAlerte($wishlist, $produit, $nouveauPrix, $pourcentage)
    {
        try {
            $user = $wishlist->utilisateur;
            if (!$user || !$user->email) return;

            $subject = "Baisse de prix : " . $produit->nom;
            $body = "Bonjour {$user->nom},\n\n";
            $body .= "Bonne nouvelle ! Le produit '{$produit->nom}' que vous aviez en favoris a baisse de prix !\n\n";
            $body .= "Ancien prix : {$wishlist->prix_ajout} USD\n";
            $body .= "Nouveau prix : {$nouveauPrix} USD\n";
            $body .= "Reduction : {$pourcentage}%\n\n";
            $body .= "Ne manquez pas cette opportunite !\n\n";
            $body .= "Cordialement,\nL'equipe ShopPro";

            Mail::raw($body, function ($message) use ($user, $subject) {
                $message->to($user->email)->subject($subject);
            });

            Log::info("Email alerte prix envoye a {$user->email} pour {$produit->nom}");
        } catch (\Exception $e) {
            Log::error("Erreur envoi email alerte : " . $e->getMessage());
        }
    }
}