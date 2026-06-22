<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Statut commande {{ $commande->numero_commande }}</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9fafb; }
        .container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { padding: 40px 30px; text-align: center; color: white; }
        .header.confirmee { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
        .header.expediee { background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%); }
        .header.livree { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .header.annulee { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .content { padding: 30px; }
        .status-icon { font-size: 64px; text-align: center; margin: 20px 0; }
        .info-box { background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; }
        .footer { background: #f3f4f6; padding: 20px 30px; text-align: center; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ strtolower($nouveauStatut) }}">
            <div class="status-icon">{{ $icone }}</div>
            <h1 style="margin: 0;">{{ $titre }}</h1>
        </div>

        <div class="content">
            <p>Bonjour <strong>{{ $client->nom }}</strong>,</p>
            
            <p>{{ $message }}</p>

            <div class="info-box">
                <strong>📋 Détails de la commande</strong><br>
                Numéro : <strong>{{ $commande->numero_commande }}</strong><br>
                Montant : <strong>{{ number_format($commande->montant_total, 2, ',', ' ') }} €</strong><br>
                Nouveau statut : <strong>{{ ucfirst(str_replace('_', ' ', $nouveauStatut)) }}</strong>
            </div>

            @if($nouveauStatut === 'expediee' && $commande->livraisons->first())
            <div class="info-box">
                <strong>🚚 Informations de livraison</strong><br>
                @if($commande->livraisons->first()->code_suivi)
                    Code de suivi : <strong>{{ $commande->livraisons->first()->code_suivi }}</strong><br>
                @endif
                @if($commande->livraisons->first()->transporteur)
                    Transporteur : <strong>{{ $commande->livraisons->first()->transporteur }}</strong>
                @endif
            </div>
            @endif

            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/commandes/' . $commande->numero_commande) }}" class="button">Voir ma commande</a>
            </p>

            <p style="font-size: 14px; color: #6b7280; text-align: center;">
                Une question ? <a href="mailto:support@shoppro.com">support@shoppro.com</a>
            </p>
        </div>

        <div class="footer">
            <p><strong>ShopPro</strong> - La meilleure boutique en ligne</p>
            <p>© {{ date('Y') }} ShopPro. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>