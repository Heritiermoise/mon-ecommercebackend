<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    public function envoyerBienvenue(User $user)
    {
        try {
            if (!$user->email) {
                Log::warning("Email bienvenue non envoye : utilisateur sans email");
                return false;
            }

            $subject = "Bienvenue sur ShopPro !";
            $body = "Bonjour {$user->nom},\n\nBienvenue sur ShopPro ! Nous sommes ravis de vous compter parmi nos clients.\n\nDecouvrez nos produits de qualite et profitez de nos offres exclusives.\n\nCordialement,\nL'equipe ShopPro";

            Mail::raw($body, function ($message) use ($user, $subject) {
                $message->to($user->email)->subject($subject);
            });

            Log::info("Email bienvenue envoye a {$user->email}");
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur email bienvenue : " . $e->getMessage());
            return false;
        }
    }

    public function envoyer2FA($user, $code)
    {
        try {
            if (!$user->email) {
                Log::warning("Email 2FA non envoye : utilisateur sans email");
                return false;
            }

            $subject = "Code de verification 2FA - ShopPro";
            $body = "Bonjour {$user->nom},\n\nVotre code de verification a deux facteurs est : {$code}\n\nCe code expire dans 10 minutes.\n\nSi vous n'avez pas demande ce code, ignorez cet email.\n\nCordialement,\nL'equipe ShopPro";

            Mail::raw($body, function ($message) use ($user, $subject) {
                $message->to($user->email)->subject($subject);
            });

            Log::info("Email 2FA envoye a {$user->email}");
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur email 2FA : " . $e->getMessage());
            return false;
        }
    }

    public function envoyerFacture(Commande $commande)
    {
        try {
            $client = $commande->utilisateur;
            if (!$client || !$client->email) {
                Log::warning("Facture non envoyee : client sans email");
                return false;
            }

            $subject = "Facture - Commande " . $commande->numero_commande;
            
            $body = "Bonjour {$client->nom},\n\n";
            $body .= "Voici votre facture pour la commande {$commande->numero_commande}.\n\n";
            $body .= "DETAILS DE LA COMMANDE :\n";
            $body .= "========================\n\n";
            
            foreach ($commande->articles as $article) {
                $body .= "- {$article->produit_nom} x{$article->quantite} : " . number_format($article->prix_total, 2) . " USD\n";
            }
            
            $body .= "\nSous-total : " . number_format($commande->montant_total, 2) . " USD\n";
            $body .= "Livraison : " . number_format($commande->frais_livraison, 2) . " USD\n";
            
            if ($commande->reduction > 0) {
                $body .= "Reduction : -" . number_format($commande->reduction, 2) . " USD\n";
            }
            
            $total = $commande->montant_total + $commande->frais_livraison - $commande->reduction;
            $body .= "\nTOTAL : " . number_format($total, 2) . " USD\n";
            
            $body .= "\nMerci pour votre confiance !\n\nCordialement,\nL'equipe ShopPro";

            Mail::raw($body, function ($message) use ($client, $subject) {
                $message->to($client->email)->subject($subject);
            });

            Log::info("Facture envoyee a {$client->email} pour commande {$commande->numero_commande}");
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur facture : " . $e->getMessage());
            return false;
        }
    }

    public function envoyerConfirmationCommande(Commande $commande)
    {
        try {
            $client = $commande->utilisateur;
            if (!$client || !$client->email) {
                Log::warning("Confirmation commande non envoyee : client sans email");
                return false;
            }

            $subject = "Confirmation de commande - " . $commande->numero_commande;
            
            $body = "Bonjour {$client->nom},\n\n";
            $body .= "Votre commande {$commande->numero_commande} a ete enregistree avec succes !\n\n";
            $body .= "REFERENCE : {$commande->numero_commande}\n";
            $body .= "MONTANT TOTAL : " . number_format($commande->montant_total + $commande->frais_livraison - $commande->reduction, 2) . " USD\n";
            $body .= "STATUT : En attente de paiement\n\n";
            $body .= "Prochaine etape : Effectuez le paiement pour confirmer votre commande.\n\n";
            $body .= "Merci pour votre confiance !\n\nCordialement,\nL'equipe ShopPro";

            Mail::raw($body, function ($message) use ($client, $subject) {
                $message->to($client->email)->subject($subject);
            });

            Log::info("Confirmation commande envoyee a {$client->email}");
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur confirmation commande : " . $e->getMessage());
            return false;
        }
    }

    public function envoyerNotificationStatut(Commande $commande, string $nouveauStatut)
    {
        try {
            $client = $commande->utilisateur;
            if (!$client || !$client->email) {
                return false;
            }

            $subject = "Mise a jour de votre commande " . $commande->numero_commande;
            $body = "Bonjour {$client->nom},\n\nLe statut de votre commande {$commande->numero_commande} a ete mis a jour : {$nouveauStatut}\n\nCordialement,\nL'equipe ShopPro";

            Mail::raw($body, function ($message) use ($client, $subject) {
                $message->to($client->email)->subject($subject);
            });

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur notification statut : " . $e->getMessage());
            return false;
        }
    }
}