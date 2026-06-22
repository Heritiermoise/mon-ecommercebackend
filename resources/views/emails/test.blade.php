<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Email ShopPro</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            max-width: 600px; 
            margin: 0 auto; 
            padding: 20px;
            background: #f9fafb;
        }
        .container { 
            background: white; 
            border-radius: 12px; 
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 40px 30px; 
            text-align: center;
        }
        .header h1 { margin: 0; font-size: 32px; }
        .header p { margin: 10px 0 0 0; opacity: 0.9; }
        .content { padding: 40px 30px; }
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            background: #f3f4f6;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
        .emoji { font-size: 48px; text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛍️ ShopPro</h1>
            <p>Test de configuration email</p>
        </div>

        <div class="content">
            <div class="emoji">✅</div>
            
            <h2 style="color: #10b981; text-align: center;">Configuration réussie !</h2>
            
            <div class="success-box">
                <strong>🎉 Félicitations !</strong><br>
                Votre système d'email fonctionne parfaitement. Vous pouvez maintenant envoyer des emails automatiques à vos clients.
            </div>

            <div class="info-box">
                <strong>📧 Prochaines étapes :</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Lorsqu'un client s'inscrit → Email de bienvenue</li>
                    <li>Lorsqu'une commande est créée → Facture automatique</li>
                    <li>Lorsqu'une commande est expédiée → Notification</li>
                </ul>
            </div>

            <p style="text-align: center; margin-top: 30px;">
                <strong>Heure d'envoi :</strong> {{ date('d/m/Y H:i:s') }}<br>
                <strong>Expéditeur :</strong> vitalheritier1@gmail.com
            </p>
        </div>

        <div class="footer">
            <p><strong>ShopPro</strong> - La meilleure boutique en ligne</p>
            <p>Cet email a été envoyé automatiquement suite à un test de configuration.</p>
            <p>© {{ date('Y') }} ShopPro. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>