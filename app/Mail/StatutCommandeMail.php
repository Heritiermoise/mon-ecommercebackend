<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StatutCommandeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $commande;
    public $client;
    public $nouveauStatut;
    public $titre;
    public $message;
    public $icone;

    public function __construct(Commande $commande, string $nouveauStatut)
    {
        $this->commande = $commande->load(['articles', 'utilisateur', 'livraisons']);
        $this->client = $commande->utilisateur;
        $this->nouveauStatut = $nouveauStatut;

        $this->configurerStatut();
    }

    private function configurerStatut()
    {
        $nom = $this->client->nom;
        $num = $this->commande->numero_commande;

        switch ($this->nouveauStatut) {
            case 'confirmee':
                $this->titre = "Commande confirmée !";
                $this->message = "Bonne nouvelle $nom ! Votre commande #$num a été confirmée et est en cours de préparation.";
                $this->icone = "✅";
                break;
            case 'expediee':
                $this->titre = "Commande expédiée !";
                $this->message = "Votre commande #$num a été expédiée. Elle est en route vers vous !";
                $this->icone = "🚚";
                break;
            case 'livree':
                $this->titre = "Commande livrée !";
                $this->message = "Votre commande #$num a été livrée. Merci pour votre achat !";
                $this->icone = "🎉";
                break;
            case 'annulee':
                $this->titre = "Commande annulée";
                $this->message = "Votre commande #$num a été annulée. Le remboursement sera effectué sous 5-7 jours.";
                $this->icone = "❌";
                break;
            default:
                $this->titre = "Mise à jour de votre commande";
                $this->message = "Le statut de votre commande #$num a été mis à jour.";
                $this->icone = "📦";
        }
    }

    public function build()
    {
        return $this->subject($this->titre . " - Commande #{$this->commande->numero_commande}")
                    ->view('emails.statut-commande');
    }
}