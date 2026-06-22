<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Confirmation de commande</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px 20px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">✅ Commande confirmée !</h1>
        </div>

        <!-- Content -->
        <div style="padding: 40px 30px;">
            <p style="font-size: 18px; color: #333333; margin-bottom: 20px;">
                Bonjour <strong>{{ $commande->utilisateur->nom }}</strong>,
            </p>
            
            <p style="color: #666666; line-height: 1.6; margin-bottom: 20px;">
                Merci pour votre commande ! Voici les détails :
            </p>

            <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 4px;">
                <h3 style="color: #333333; margin-top: 0;">Commande #{{ $commande->numero_commande }}</h3>
                <p style="color: #666666; margin: 10px 0;"><strong>Date :</strong> {{ $commande->created_at->format('d/m/Y H:i') }}</p>
                <p style="color: #666666; margin: 10px 0;"><strong>Statut :</strong> 
                    <span style="background-color: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                        En attente de paiement
                    </span>
                </p>
            </div>

            <h3 style="color: #333333; margin-top: 30px;">Articles commandés :</h3>
            
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e0e0e0;">Produit</th>
                        <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e0e0e0;">Qté</th>
                        <th style="padding: 12px; text-align: right; border-bottom: 2px solid #e0e0e0;">Prix</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($commande->articles as $article)
                    <tr>
                        <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;">{{ $article->produit_nom }}</td>
                        <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e0e0e0;">{{ $article->quantite }}</td>
                        <td style="padding: 12px; text-align: right; border-bottom: 1px solid #e0e0e0;">${{ number_format($article->prix_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 4px;">
                <div style="display: flex; justify-content: space-between; margin: 10px 0;">
                    <span style="color: #666666;">Sous-total :</span>
                    <span style="color: #333333; font-weight: bold;">${{ number_format($commande->montant_total, 2) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin: 10px 0;">
                    <span style="color: #666666;">Livraison :</span>
                    <span style="color: #333333; font-weight: bold;">${{ number_format($commande->frais_livraison, 2) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin: 10px 0; padding-top: 10px; border-top: 2px solid #e0e0e0;">
                    <span style="color: #333333; font-size: 18px; font-weight: bold;">Total :</span>
                    <span style="color: #10b981; font-size: 24px; font-weight: bold;">${{ number_format($commande->montant_total + $commande->frais_livraison - $commande->reduction, 2) }}</span>
                </div>
            </div>

            <div style="text-align: center; margin: 40px 0;">
                <a href="{{ config('app.url') }}/paiement/{{ $commande->numero_commande }}" style="display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; padding: 15px 40px; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px;">
                    Procéder au paiement
                </a>
            </div>

            <p style="color: #666666; line-height: 1.6;">
                Vous recevrez un email de confirmation une fois le paiement validé.
            </p>
        </div>

        <!-- Footer -->
        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="color: #999999; font-size: 12px; margin: 0;">
                © {{ date('Y') }} ShopPro. Tous droits réservés.
            </p>
        </div>
    </div>
</body>
</html>