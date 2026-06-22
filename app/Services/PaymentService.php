<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Paiement;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Initier un paiement
     */
    public function initierPaiement(Commande $commande, string $methode, ?string $telephone = null)
    {
        $paiement = Paiement::create([
            'commande_id' => $commande->id,
            'methode' => $methode,
            'montant' => $commande->montant_total,
            'statut' => 'en_attente',
        ]);

        switch ($methode) {
            case 'mpesa':
                return $this->initierMpesa($paiement, $telephone);
            case 'orange_money':
                return $this->initierOrangeMoney($paiement, $telephone);
            case 'airtel_money':
                return $this->initierAirtelMoney($paiement, $telephone);
            case 'carte':
                return $this->initierCarte($paiement);
            default:
                throw new \Exception('Méthode de paiement non supportée');
        }
    }

    /**
     * M-Pesa (Safaricom)
     */
    private function initierMpesa(Paiement $paiement, ?string $telephone)
    {
        if (!$telephone) {
            throw new \Exception('Numéro de téléphone requis pour M-Pesa');
        }

        // Nettoyer le numéro
        $telephone = preg_replace('/[^0-9]/', '', $telephone);
        if (strlen($telephone) === 9) {
            $telephone = '254' . $telephone; // Format international Kenya
        }

        try {
            // Obtenir le token d'authentification
            $consumerKey = config('services.mpesa.consumer_key');
            $consumerSecret = config('services.mpesa.consumer_secret');
            
            $tokenResponse = Http::withBasicAuth($consumerKey, $consumerSecret)
                ->get('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

            $accessToken = $tokenResponse->json('access_token');

            // Initier le paiement STK Push
            $timestamp = now()->format('YmdHis');
            $password = base64_encode(config('services.mpesa.shortcode') . config('services.mpesa.passkey') . $timestamp);

            $response = Http::withToken($accessToken)
                ->post('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest', [
                    'BusinessShortCode' => config('services.mpesa.shortcode'),
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => ceil($paiement->montant),
                    'PartyA' => $telephone,
                    'PartyB' => config('services.mpesa.shortcode'),
                    'PhoneNumber' => $telephone,
                    'CallBackURL' => route('payment.webhook.mpesa'),
                    'AccountReference' => $paiement->commande->numero_commande,
                    'TransactionDesc' => 'Paiement commande ' . $paiement->commande->numero_commande,
                ]);

            $result = $response->json();

            if ($response->successful() && isset($result['ResponseCode']) && $result['ResponseCode'] == 0) {
                $paiement->update([
                    'id_transaction_fournisseur' => $result['CheckoutRequestID'],
                    'details_paiement' => json_encode($result),
                ]);

                return [
                    'success' => true,
                    'methode' => 'mpesa',
                    'checkout_request_id' => $result['CheckoutRequestID'],
                    'merchant_request_id' => $result['MerchantRequestID'],
                    'message' => 'Une demande de paiement a été envoyée à votre téléphone',
                ];
            } else {
                throw new \Exception($result['errorMessage'] ?? 'Erreur M-Pesa');
            }

        } catch (\Exception $e) {
            Log::error('Erreur M-Pesa', ['error' => $e->getMessage()]);
            throw new \Exception('Erreur lors de l\'initiation du paiement M-Pesa: ' . $e->getMessage());
        }
    }

    /**
     * Orange Money
     */
    private function initierOrangeMoney(Paiement $paiement, ?string $telephone)
    {
        if (!$telephone) {
            throw new \Exception('Numéro de téléphone requis pour Orange Money');
        }

        try {
            $apiKey = config('services.orange_money.api_key');
            $merchantKey = config('services.orange_money.merchant_key');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.orange.com/orange-money-webpay/dev/v1/webpayment', [
                'merchant_key' => $merchantKey,
                'currency' => 'EUR',
                'amount' => $paiement->montant,
                'reference' => $paiement->commande->numero_commande,
                'return_url' => config('app.frontend_url') . '/commandes/' . $paiement->commande->numero_commande,
                'cancel_url' => config('app.frontend_url') . '/panier',
                'notif_url' => route('payment.webhook.orange'),
                'lang' => 'fr',
                'display_text' => 'Paiement commande ' . $paiement->commande->numero_commande,
            ]);

            $result = $response->json();

            if ($response->successful()) {
                $paiement->update([
                    'id_transaction_fournisseur' => $result['order_id'] ?? uniqid(),
                    'details_paiement' => json_encode($result),
                ]);

                return [
                    'success' => true,
                    'methode' => 'orange_money',
                    'redirect_url' => $result['redirect_url'] ?? null,
                    'message' => 'Redirection vers Orange Money',
                ];
            } else {
                throw new \Exception($result['message'] ?? 'Erreur Orange Money');
            }

        } catch (\Exception $e) {
            Log::error('Erreur Orange Money', ['error' => $e->getMessage()]);
            throw new \Exception('Erreur lors de l\'initiation du paiement Orange Money');
        }
    }

    /**
     * Airtel Money
     */
    private function initierAirtelMoney(Paiement $paiement, ?string $telephone)
    {
        if (!$telephone) {
            throw new \Exception('Numéro de téléphone requis pour Airtel Money');
        }

        try {
            // Simulation Airtel Money (API similaire)
            $response = Http::post('https://api.airtel.money/v1/payment', [
                'api_key' => config('services.airtel_money.api_key'),
                'phone' => $telephone,
                'amount' => $paiement->montant,
                'reference' => $paiement->commande->numero_commande,
                'callback_url' => route('payment.webhook.airtel'),
            ]);

            $result = $response->json();

            if ($response->successful()) {
                $paiement->update([
                    'id_transaction_fournisseur' => $result['transaction_id'] ?? uniqid(),
                    'details_paiement' => json_encode($result),
                ]);

                return [
                    'success' => true,
                    'methode' => 'airtel_money',
                    'transaction_id' => $result['transaction_id'],
                    'message' => 'Une demande de paiement a été envoyée à votre téléphone',
                ];
            } else {
                throw new \Exception($result['message'] ?? 'Erreur Airtel Money');
            }

        } catch (\Exception $e) {
            Log::error('Erreur Airtel Money', ['error' => $e->getMessage()]);
            throw new \Exception('Erreur lors de l\'initiation du paiement Airtel Money');
        }
    }

    /**
     * Carte bancaire (Stripe)
     */
    private function initierCarte(Paiement $paiement)
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Commande ' . $paiement->commande->numero_commande,
                        ],
                        'unit_amount' => $paiement->montant * 100, // En centimes
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => config('app.frontend_url') . '/commandes/' . $paiement->commande->numero_commande . '?success=true',
                'cancel_url' => config('app.frontend_url') . '/panier?cancelled=true',
                'metadata' => [
                    'commande_id' => $paiement->commande->id,
                    'numero_commande' => $paiement->commande->numero_commande,
                ],
            ]);

            $paiement->update([
                'id_transaction_fournisseur' => $session->id,
                'details_paiement' => json_encode(['session_id' => $session->id]),
            ]);

            return [
                'success' => true,
                'methode' => 'carte',
                'checkout_url' => $session->url,
                'session_id' => $session->id,
                'message' => 'Redirection vers Stripe',
            ];

        } catch (\Exception $e) {
            Log::error('Erreur Stripe', ['error' => $e->getMessage()]);
            throw new \Exception('Erreur lors de l\'initiation du paiement par carte');
        }
    }

    /**
     * Webhook M-Pesa
     */
    public function handleMpesaWebhook($data)
    {
        Log::info('Webhook M-Pesa reçu', $data);

        $checkoutRequestId = $data['Body']['stkCallback']['CheckoutRequestID'] ?? null;
        $resultCode = $data['Body']['stkCallback']['ResultCode'] ?? null;

        if (!$checkoutRequestId) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Invalid data']);
        }

        $paiement = Paiement::where('id_transaction_fournisseur', $checkoutRequestId)->first();

        if (!$paiement) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Payment not found']);
        }

        if ($resultCode == 0) {
            // Paiement réussi
            $mpesaReceipt = $data['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
            $transactionId = collect($mpesaReceipt)->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;

            $paiement->update([
                'statut' => 'succes',
                'id_transaction_fournisseur' => $transactionId ?? $checkoutRequestId,
                'paye_le' => now(),
            ]);

            $this->confirmerPaiement($paiement);
        } else {
            // Paiement échoué
            $paiement->update([
                'statut' => 'echoue',
                'details_paiement' => json_encode($data),
            ]);

            $this->notifierEchecPaiement($paiement);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    /**
     * Webhook Orange Money
     */
    public function handleOrangeMoneyWebhook($data)
    {
        Log::info('Webhook Orange Money reçu', $data);

        $orderId = $data['order_id'] ?? null;
        $status = $data['status'] ?? null;

        $paiement = Paiement::where('id_transaction_fournisseur', $orderId)->first();

        if (!$paiement) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        if ($status === 'SUCCESS') {
            $paiement->update([
                'statut' => 'succes',
                'paye_le' => now(),
            ]);

            $this->confirmerPaiement($paiement);
        } else {
            $paiement->update(['statut' => 'echoue']);
            $this->notifierEchecPaiement($paiement);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Webhook Stripe
     */
    public function handleStripeWebhook($payload, $sigHeader)
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Exception $e) {
            Log::error('Erreur webhook Stripe', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $paiement = Paiement::where('id_transaction_fournisseur', $session->id)->first();

            if ($paiement) {
                $paiement->update([
                    'statut' => 'succes',
                    'paye_le' => now(),
                ]);

                $this->confirmerPaiement($paiement);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Confirmer un paiement réussi
     */
    private function confirmerPaiement(Paiement $paiement)
    {
        $commande = $paiement->commande;
        $commande->load('utilisateur');

        // Mettre à jour le statut de la commande
        $commande->update([
            'statut_paiement' => 'paye',
            'statut' => 'payee',
        ]);

        // Créer l'historique
        \App\Models\HistoriqueStatutCommande::create([
            'commande_id' => $commande->id,
            'ancien_statut' => 'en_attente',
            'nouveau_statut' => 'payee',
            'commentaire' => 'Paiement confirmé automatiquement',
        ]);

        // Décrémenter le stock
        foreach ($commande->articles as $article) {
            $article->produit->decrementerStock($article->quantite);
        }

        // Notification au client
        Notification::create([
            'utilisateur_id' => $commande->utilisateur_id,
            'titre' => 'Paiement confirmé ✅',
            'message' => "Votre commande {$commande->numero_commande} a été payée avec succès. Nous préparons votre commande.",
            'type' => 'paiement',
            'lien' => '/commandes/' . $commande->numero_commande,
        ]);

        // Notification à l'admin
        $admins = \App\Models\User::admins()->get();
        foreach ($admins as $admin) {
            Notification::create([
                'utilisateur_id' => $admin->id,
                'titre' => 'Nouvelle commande payée 💰',
                'message' => "Commande {$commande->numero_commande} - {$commande->montant_total}€",
                'type' => 'commande',
                'lien' => '/admin/commandes',
            ]);
        }
    }

    /**
     * Notifier un échec de paiement
     */
    private function notifierEchecPaiement(Paiement $paiement)
    {
        $commande = $paiement->commande;

        Notification::create([
            'utilisateur_id' => $commande->utilisateur_id,
            'titre' => 'Échec du paiement ❌',
            'message' => "Le paiement de votre commande {$commande->numero_commande} a échoué. Veuillez réessayer.",
            'type' => 'paiement',
            'lien' => '/commandes/' . $commande->numero_commande,
        ]);
    }
}