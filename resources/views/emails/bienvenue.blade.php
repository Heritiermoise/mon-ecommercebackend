<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bienvenue sur ShopPro</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9fafb; }
        .container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 32px; }
        .header p { margin: 10px 0 0 0; opacity: 0.9; }
        .content { padding: 40px 30px; }
        .welcome-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .promo-box { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .promo-code { display: inline-block; background: #1e40af; color: white; padding: 10px 20px; border-radius: 8px; font-size: 24px; font-weight: bold; letter-spacing: 2px; margin: 10px 0; }
        .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; }
        .footer { background: #f3f4f6; padding: 20px 30px; text-align: center; font-size: 12px; color: #6b7280; }
        .features { display: flex; justify-content: space-around; margin: 30px 0; }
        .feature { text-align: center; flex: 1; }
        .feature-icon { font-size: 32px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Bienvenue {{ $user->nom }} !</h1>
            <p>Votre compte ShopPro a été créé avec succès</p>
        </div>

        <div class="content">
            <p>Bonjour <strong>{{ $user->nom }}</strong>,</p>
            
            <p>Nous sommes ravis de vous accueillir sur <strong>ShopPro</strong>, votre nouvelle boutique en ligne de confiance !</p>

            <div class="welcome-box">
                <strong>✨ Votre compte est actif</strong><br>
                Email : {{ $user->email }}<br>
                Vous pouvez dès maintenant parcourir nos produits et passer vos premières commandes.
            </div>

            <div class="promo-box">
                <strong>🎁 OFFRE DE BIENVENUE</strong><br>
                <p style="margin: 10px 0;">Profitez de <strong>10% de réduction</strong> sur votre première commande !</p>
                <div class="promo-code">BIENVENUE10</div>
                <p style="font-size: 12px; color: #6b7280;">Code valable sur tout le site pendant 30 jours</p>
            </div>

            <div class="features">
                <div class="feature">
                    <div class="feature-icon">🚚</div>
                    <strong>Livraison rapide</strong>
                    <p style="font-size: 12px;">Gratuite dès 100€</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">💳</div>
                    <strong>Paiement sécurisé</strong>
                    <p style="font-size: 12px;">M-Pesa, CB, etc.</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">🎁</div>
                    <strong>Programme fidélité</strong>
                    <p style="font-size: 12px;">1€ = 1 point</p>
                </div>
            </div>

            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/') }}" class="button">Commencer mes achats</a>
            </p>

            <p style="font-size: 14px; color: #6b7280; text-align: center;">
                Besoin d'aide ? Contactez-nous à <a href="mailto:support@shoppro.com">support@shoppro.com</a>
            </p>
        </div>

        <div class="footer">
            <p><strong>ShopPro</strong> - La meilleure boutique en ligne</p>
            <p>© {{ date('Y') }} ShopPro. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>