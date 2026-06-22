<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Facture - {{ $commande->numero_commande }}</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; background: #f9fafb; }
        .container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .header p { margin: 10px 0 0 0; opacity: 0.9; }
        .content { padding: 30px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-box { background: #f9fafb; padding: 20px; border-radius: 8px; }
        .info-box h3 { margin: 0 0 10px 0; color: #667eea; font-size: 14px; text-transform: uppercase; }
        .info-box p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #667eea; color: white; padding: 12px; text-align: left; font-size: 14px; }
        td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
        .totals { background: #f9fafb; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .total-row.final { border-top: 2px solid #667eea; margin-top: 10px; padding-top: 15px; font-size: 20px; font-weight: bold; color: #667eea; }
        .badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; margin: 20px 0; }
        .footer { background: #f3f4f6; padding: 20px 30px; text-align: center; font-size: 12px; color: #6b7280; }
        .help-box { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛍️ ShopPro</h1>
            <p>Confirmation de votre commande</p>
        </div>

        <div class="content">
            <h2>Bonjour {{ $client->nom }},</h2>
            <p>Merci pour votre commande ! Voici votre facture détaillée.</p>

            <div class="info-grid">
                <div class="info-box">
                    <h3>📋 Informations commande</h3>
                    <p><strong>N° :</strong> {{ $commande->numero_commande }}</p>
                    <p><strong>Date :</strong> {{ $commande->created_at->format('d/m/Y à H:i') }}</p>
                    <p><strong>Statut :</strong> <span class="badge badge-warning">{{ ucfirst(str_replace('_', ' ', $commande->statut)) }}</span></p>
                </div>
                <div class="info-box">
                    <h3>📍 Adresse de livraison</h3>
                    @if($commande->adresseLivraison)
                        <p><strong>{{ $commande->adresseLivraison->nom_complet }}</strong></p>
                        <p>{{ $commande->adresseLivraison->adresse }}</p>
                        <p>{{ $commande->adresseLivraison->ville }}</p>
                        <p>📞 {{ $commande->adresseLivraison->telephone }}</p>
                    @else
                        <p>Adresse non renseignée</p>
                    @endif
                </div>
            </div>

            <h3>📦 Articles commandés</h3>
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th style="text-align: center;">Qté</th>
                        <th style="text-align: right;">Prix unit.</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($commande->articles as $article)
                    <tr>
                        <td>{{ $article->produit_nom }}</td>
                        <td style="text-align: center;">{{ $article->quantite }}</td>
                        <td style="text-align: right;">{{ number_format($article->prix, 2, ',', ' ') }} €</td>
                        <td style="text-align: right;"><strong>{{ number_format($article->prix_total, 2, ',', ' ') }} €</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals">
                <div class="total-row">
                    <span>Sous-total</span>
                    <span>{{ number_format($commande->montant_total, 2, ',', ' ') }} €</span>
                </div>
                <div class="total-row">
                    <span>Frais de livraison</span>
                    <span>{{ $commande->frais_livraison > 0 ? number_format($commande->frais_livraison, 2, ',', ' ') . ' €' : 'GRATUIT' }}</span>
                </div>
                @if($commande->reduction > 0)
                <div class="total-row" style="color: #10b981;">
                    <span>Réduction</span>
                    <span>-{{ number_format($commande->reduction, 2, ',', ' ') }} €</span>
                </div>
                @endif
                <div class="total-row final">
                    <span>TOTAL</span>
                    <span>{{ number_format($commande->montant_total + $commande->frais_livraison - $commande->reduction, 2, ',', ' ') }} €</span>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/commandes/' . $commande->numero_commande) }}" class="button">Suivre ma commande</a>
            </div>

            <div class="help-box">
                <strong>💡 Besoin d'aide ?</strong><br>
                Contactez notre support à <a href="mailto:support@shoppro.com">support@shoppro.com</a><br>
                ou appelez le +33 1 23 45 67 89 (du lundi au samedi, 9h-18h)
            </div>
        </div>

        <div class="footer">
            <p><strong>ShopPro</strong> - La meilleure boutique en ligne</p>
            <p>Cet email est votre facture officielle. Conservez-le précieusement.</p>
            <p>© {{ date('Y') }} ShopPro. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>