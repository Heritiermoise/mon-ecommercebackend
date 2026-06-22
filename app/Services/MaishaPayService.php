<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MaishaPayService
{
    private $merchantId;
    private $publicKey;
    private $secretKey;
    private $baseUrl;
    private $env;

    public function __construct()
    {
        $this->env = config('maishapay.env');
        $this->merchantId = config('maishapay.merchant_id');
        $this->publicKey = config('maishapay.public_key');
        $this->secretKey = config('maishapay.secret_key');
        $this->baseUrl = config('maishapay.urls.' . $this->env);
    }

    public function initierPaiement($commande, $montant, $description, $callbackUrl, $cancelUrl)
    {
        try {
            $reference = 'CMD-' . $commande->numero_commande . '-' . Str::random(8);
            
            $payload = [
                'merchant_id' => $this->merchantId,
                'public_key' => $this->publicKey,
                'reference' => $reference,
                'amount' => number_format($montant, 2, '.', ''),
                'currency' => 'USD',
                'description' => $description,
                'callback_url' => $callbackUrl,
                'cancel_url' => $cancelUrl,
                'customer' => [
                    'email' => $commande->utilisateur->email,
                    'name' => $commande->utilisateur->nom,
                ],
                'metadata' => [
                    'commande_id' => $commande->id,
                    'numero_commande' => $commande->numero_commande,
                    'utilisateur_id' => $commande->utilisateur_id,
                ],
            ];

            Log::info('MaishaPay - Initiation paiement', [
                'reference' => $reference,
                'montant' => $montant,
                'commande' => $commande->numero_commande,
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . '/payments/initiate', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('MaishaPay - Paiement initie avec succes', [
                    'response' => $data,
                ]);

                return [
                    'success' => true,
                    'payment_url' => $data['data']['payment_url'] ?? $data['payment_url'] ?? null,
                    'reference' => $reference,
                    'transaction_id' => $data['data']['transaction_id'] ?? $data['transaction_id'] ?? null,
                    'raw_response' => $data,
                ];
            } else {
                Log::error('MaishaPay - Erreur initiation', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Erreur lors de l initiation du paiement',
                    'error' => $response->body(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('MaishaPay - Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur de connexion avec MaishaPay',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function verifierPaiement($reference)
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . '/payments/verify', [
                    'merchant_id' => $this->merchantId,
                    'public_key' => $this->publicKey,
                    'reference' => $reference,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur verification',
            ];
        } catch (\Exception $e) {
            Log::error('MaishaPay - Erreur verification', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function verifierSignatureWebhook($payload, $signature)
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->secretKey);
        return hash_equals($expectedSignature, $signature);
    }

    public function traiterWebhook($data)
    {
        try {
            $reference = $data['reference'] ?? null;
            $status = $data['status'] ?? null;
            $transactionId = $data['transaction_id'] ?? null;
            $amount = $data['amount'] ?? 0;

            Log::info('MaishaPay - Webhook recu', [
                'reference' => $reference,
                'status' => $status,
                'transaction_id' => $transactionId,
            ]);

            if (!$reference || !$status) {
                return ['success' => false, 'message' => 'Donnees webhook invalides'];
            }

            $commande = \App\Models\Commande::where('numero_commande', 'like', '%' . explode('-', $reference)[1] . '%')
                ->first();

            if (!$commande) {
                return ['success' => false, 'message' => 'Commande non trouvee'];
            }

            $paiement = \App\Models\Paiement::where('commande_id', $commande->id)->first();

            if ($status === 'success' || $status === 'completed') {
                if ($paiement) {
                    $paiement->update([
                        'statut' => 'paye',
                        'paye_le' => now(),
                        'reference_transaction' => $transactionId ?? $reference,
                        'methode' => 'maishapay',
                    ]);
                } else {
                    \App\Models\Paiement::create([
                        'commande_id' => $commande->id,
                        'methode' => 'maishapay',
                        'montant' => $amount,
                        'statut' => 'paye',
                        'paye_le' => now(),
                        'reference_transaction' => $transactionId ?? $reference,
                    ]);
                }

                $commande->update([
                    'statut_paiement' => 'paye',
                    'statut' => 'payee',
                ]);

                $this->mettreAJourStock($commande);
                $this->notifierAdmin($commande, $amount);

                return ['success' => true, 'message' => 'Paiement confirme'];
            } elseif ($status === 'failed' || $status === 'cancelled') {
                if ($paiement) {
                    $paiement->update([
                        'statut' => 'echoue',
                    ]);
                }

                return ['success' => true, 'message' => 'Paiement echoue enregistre'];
            }

            return ['success' => true, 'message' => 'Statut enregistre'];
        } catch (\Exception $e) {
            Log::error('MaishaPay - Erreur traitement webhook', [
                'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function mettreAJourStock($commande)
    {
        foreach ($commande->articles as $article) {
            $produit = \App\Models\Produit::find($article->produit_id);
            if ($produit) {
                $nouveauStock = max(0, $produit->quantite_stock - $article->quantite);
                $produit->update(['quantite_stock' => $nouveauStock]);
            }
        }
    }

    private function notifierAdmin($commande, $montant)
    {
        $admins = \App\Models\User::whereIn('role', ['administrateur', 'super_administrateur'])->get();
        
        foreach ($admins as $admin) {
            \App\Models\Notification::create([
                'utilisateur_id' => $admin->id,
                'type' => 'paiement_recu',
                'titre' => 'Paiement MaishaPay recu',
                'message' => "La commande {$commande->numero_commande} a ete payee via MaishaPay. Montant: {$montant} USD",
                'lien' => '/admin/commandes/' . $commande->numero_commande,
                'lu' => false,
            ]);
        }
    }
}