<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderInvoiceMail;

class MaishaPayController extends Controller
{
    /**
     * Initier un paiement MaishaPay
     * Retourne les donnÃ©es nÃ©cessaires pour le formulaire HTML
     */
    public function initier(Request $request, $numeroCommande)
    {
        try {
            // RÃ©cupÃ©rer la commande
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

            // VÃ©rifier si dÃ©jÃ  payÃ©e
            if ($commande->statut_paiement === 'paye') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande est deja payee'
                ], 400);
            }

            // Calculer le montant total
            $montant = (float) $commande->montant_total + 
                       (float) $commande->frais_livraison - 
                       (float) $commande->reduction;

            // Configuration MaishaPay
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

            // CrÃ©er la rÃ©fÃ©rence de transaction unique
            $transactionRef = 'CMD-' . $commande->id . '-' . time();

            // CrÃ©er l'entrÃ©e paiement
            $paiement = Paiement::updateOrCreate(
                ['commande_id' => $commande->id],
                [
                    'methode' => 'maishapay',
                    'montant' => $montant,
                    'statut' => 'en_attente',
                    'reference_transaction' => $transactionRef,
                ]
            );

            // URL de callback (retour aprÃ¨s paiement)
            $callbackUrl = url('/api/payment/maishapay/callback');

            // Retourner les donnÃ©es pour le formulaire HTML
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
     * Callback aprÃ¨s paiement MaishaPay
     * MaishaPay redirige ici avec les paramÃ¨tres de statut
     */
    public function callback(Request $request)
    {
        try {
            $status = $request->input('status');
            $description = $request->input('description');
            $transactionRefId = $request->input('transactionRefId');
            $operatorRefId = $request->input('operatorRefId');

            Log::info('MaishaPay callback reÃ§u', [
                'status' => $status,
                'description' => $description,
                'transactionRefId' => $transactionRefId,
                'operatorRefId' => $operatorRefId,
            ]);

            // Trouver le paiement par la rÃ©fÃ©rence
            $paiement = Paiement::where('reference_transaction', $transactionRefId)->first();

            if (!$paiement) {
                Log::warning('Paiement non trouvÃ©', ['reference' => $transactionRefId]);
                return redirect('/commandes?payment=error&message=Paiement+non+trouve');
            }

            $commande = $paiement->commande;

            // Status 202 = Accepted
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

                    // DÃ©crÃ©menter le stock
                    foreach ($commande->articles as $article) {
                        $produit = $article->produit;
                        if ($produit) {
                            $produit->decrement('quantite_stock', $article->quantite);
                        }
                    }
                }

                Log::info('Paiement confirmÃ©', ['reference' => $transactionRefId]);
                
                return redirect('/commandes/' . $commande->numero_commande . '?payment=success');
            } else {
                // Paiement Ã©chouÃ© ou annulÃ©
                $paiement->update([
                    'statut' => 'echoue',
                ]);

                Log::info('Paiement Ã©chouÃ©', [
                    'reference' => $transactionRefId,
                    'status' => $status,
                    'description' => $description,
                ]);

                return redirect('/commandes/' . ($commande->numero_commande ?? '') . '?payment=failed&message=' . urlencode($description ?? 'Paiement echoue'));
            }

        } catch (\Exception $e) {
            Log::error('MaishaPay callback error: ' . $e->getMessage());
            return redirect('/commandes?payment=error');
        }
    }

    /**
     * VÃ©rifier le statut d'un paiement
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
                    'message' => 'Aucun paiement trouve pour cette commande'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'statut' => $paiement->statut,
                    'montant' => (float) $paiement->montant,
                    'date_paiement' => $paiement->paye_le,
                    'reference' => $paiement->reference_transaction,
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
     * Webhook pour notification MaishaPay (optionnel)
     */
    public function notify(Request $request)
    {
        try {
            Log::info('MaishaPay webhook received', $request->all());

            $status = $request->input('status');
            $transactionRefId = $request->input('transactionRefId');

            $paiement = Paiement::where('reference_transaction', $transactionRefId)->first();

            if (!$paiement) {
                return response()->json(['success' => false, 'message' => 'Paiement non trouve'], 404);
            }

            if ($status == 202) {
                $paiement->update(['statut' => 'paye', 'paye_le' => now()]);
                
                $commande = $paiement->commande;
                if ($commande) {
                    $commande->update(['statut_paiement' => 'paye', 'statut' => 'confirmee']);
                    
                    foreach ($commande->articles as $article) {
                        $produit = $article->produit;
                        if ($produit) {
                            $produit->decrement('quantite_stock', $article->quantite);
                        }
                    }
                }
            }

            return response()->json(['success' => true, 'message' => 'Webhook traite']);

        } catch (\Exception $e) {
            Log::error('MaishaPay webhook error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
