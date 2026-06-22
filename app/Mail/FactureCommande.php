<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FactureCommande extends Mailable
{
    use Queueable, SerializesModels;

    public $commande;
    public $client;
    public $articles;

    public function __construct(Commande $commande)
    {
        $this->commande = $commande;
        $this->client = $commande->utilisateur;
        $this->articles = $commande->articles;
    }

    public function build()
    {
        return $this->subject("Facture - Commande #{$this->commande->numero_commande}")
                    ->view('emails.facture-commande');
    }
}