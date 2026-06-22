<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Paiement;
use App\Mail\OrderInvoiceMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MaishaPayController extends Controller
{
    /**
     * Initier un paiement MaishaPay
     */
    public function initier(Request $request, $numeroCommande)
    {
        try {
            $commande = Commande::where('numero_commande', $numeroCommande)
                ->where('utilisateur_id', Auth::id())
                ->with(['articles.produit', 'adresseLivraison'])
                ->first();

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvee'
                ], 404);
            }

            if ($commande->statut_paiement === 'paye') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande est deja payee'
                ], 400);
            }

            $montant = (float) $commande->montant_total + 
                       (float) $commande->frais_livraison - 
                       (float) $commande->reduction;

            $publicKey = config('services.maishapay.public_key');
            $secretKey = config('services.maishapay.secret_key');
            $checkoutUrl = config('services.maishapay.checkout_url');
            $env = config('services.maishapay.env', 'sandbox');
            $gatewayMode = $env === 'production' ? 1 : 0;

            if (!$publicKey || !$secretKey) {
                Log::error('Configuration MaishaPay manquante');
                return response()->json([
                    'success' => false,
                    'message' => 'Configuration paiement incomplete'
                ], 500);
            }

            // Reference unique
            $transactionRef = $numeroCommande . '-' . time();

            // Creer l entree paiement
            $paiement = Paiement::updateOrCreate(
                ['commande_id' => $commande->id],
                [
                    'methode' => 'maishapay',
                    'montant' => $montant,
                    'statut' => 'en_attente',
                    'reference_transaction' => $transactionRef,
                ]
            );

            // IMPORTANT : callbackUrl pointe vers le FRONTEND Next.js
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $callbackUrl = $frontendUrl . '/paiement/retour/' . $numeroCommande;

            return response()->json([
                'success' => true,
                'message' => 'Paiement initie avec succes',
                'data' => [
                    'checkout_url' => $checkoutUrl,
                    'gatewayMode' => $gatewayMode,
                    'publicApiKey' => $publicKey,
                    'secretApiKey' => $secretKey,
                    'montant' => number_format($montant, 2, '.', ''),
                    'devise' => 'USD',
                    'transactionRef' => $transactionRef,
                    'callbackUrl' => $callbackUrl,
                    'commande' => [
                        'id' => $commande->id,
                        'numero_commande' => $commande->numero_commande,
                        'montant' => $montant,
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('MaishaPayController@initier: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifier le statut d un paiement
     */
    public function verifier($numeroCommande)
    {
        try {
            $commande = Commande::where('numero_commande', $numeroCommande)
                ->where('utilisateur_id', Auth::id())
                ->first();

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvee'
                ], 404);
            }

            $paiement = Paiement::where('commande_id', $commande->id)->first();

            if (!$paiement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun paiement trouve'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'statut' => $paiement->statut,
                    'montant' => (float) $paiement->montant,
                    'date_paiement' => $paiement->paye_le,
                    'reference' => $paiement->reference_transaction,
                    'commande_statut' => $commande->statut,
                    'commande_statut_paiement' => $commande->statut_paiement,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('MaishaPayController@verifier: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Webhook MaishaPay (notification serveur a serveur)
     */
    public function notify(Request $request)
    {
        try {
            Log::info('MaishaPay webhook', $request->all());

            $status = $request->input('status');
            $transactionRefId = $request->input('transactionRefId');

            $paiement = Paiement::where('reference_transaction', $transactionRefId)->first();

            if (!$paiement) {
                return response()->json(['success' => false, 'message' => 'Paiement non trouve'], 404);
            }

            if ($status == 202 || $status === 'success' || $status === 'completed') {
                $paiement->update([
                    'statut' => 'paye',
                    'paye_le' => now(),
                ]);

                $commande = $paiement->commande;
                if ($commande) {
                    $commande->update([
                        'statut_paiement' => 'paye',
                        'statut' => 'confirmee',
                    ]);

                    // Decrementer le stock
                    foreach ($commande->articles as $article) {
                        $produit = $article->produit;
                        if ($produit) {
                            $produit->decrement('quantite_stock', $article->quantite);
                        }
                    }

                    // Envoyer la facture par email
                    try {
                        Mail::to($commande->utilisateur->email)->send(new OrderInvoiceMail($commande));
                        Log::info('Facture envoyee a ' . $commande->utilisateur->email);
                    } catch (\Exception $e) {
                        Log::error('Erreur envoi facture: ' . $e->getMessage());
                    }
                }

                Log::info('Paiement confirme', ['reference' => $transactionRefId]);
            } elseif ($status === 'failed' || $status === 'cancelled') {
                $paiement->update(['statut' => 'echoue']);
            }

            return response()->json(['success' => true, 'message' => 'Webhook traite']);

        } catch (\Exception $e) {
            Log::error('MaishaPay webhook error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Callback retour utilisateur (redirige vers frontend)
     */
    public function callback(Request $request)
    {
        try {
            $status = $request->input('status');
            $description = $request->input('description');
            $transactionRefId = $request->input('transactionRefId');
            $operatorRefId = $request->input('operatorRefId');

            Log::info('MaishaPay callback', [
                'status' => $status,
                'description' => $description,
                'transactionRefId' => $transactionRefId,
            ]);

            $paiement = Paiement::where('reference_transaction', $transactionRefId)->first();
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

            if (!$paiement) {
                return redirect($frontendUrl . '/commandes?payment=error');
            }

            $commande = $paiement->commande;

            if ($status == 202 || $description === 'Accepted') {
                $paiement->update([
                    'statut' => 'paye',
                    'paye_le' => now(),
                    'reference_transaction' => $operatorRefId ?? $transactionRefId,
                ]);

                if ($commande) {
                    $commande->update([
                        'statut_paiement' => 'paye',
                        'statut' => 'confirmee',
                    ]);

                    foreach ($commande->articles as $article) {
                        $produit = $article->produit;
                        if ($produit) {
                            $produit->decrement('quantite_stock', $article->quantite);
                        }
                    }

                    try {
                        Mail::to($commande->utilisateur->email)->send(new OrderInvoiceMail($commande));
                    } catch (\Exception $e) {
                        Log::error('Erreur envoi facture: ' . $e->getMessage());
                    }
                }

                return redirect($frontendUrl . '/paiement/succes/' . $commande->numero_commande);
            } else {
                return redirect($frontendUrl . '/paiement/echec/' . ($commande->numero_commande ?? ''));
            }

        } catch (\Exception $e) {
            Log::error('MaishaPay callback error: ' . $e->getMessage());
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect($frontendUrl . '/commandes?payment=error');
        }
    }
}