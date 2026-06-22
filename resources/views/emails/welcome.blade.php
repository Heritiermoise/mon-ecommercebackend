<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bienvenue sur ShopPro</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 32px;">🎉 Bienvenue sur ShopPro !</h1>
        </div>

        <!-- Content -->
        <div style="padding: 40px 30px;">
            <p style="font-size: 18px; color: #333333; margin-bottom: 20px;">
                Bonjour <strong>{{ $user->nom }}</strong>,
            </p>
            
            <p style="color: #666666; line-height: 1.6; margin-bottom: 20px;">
                Nous sommes ravis de vous accueillir sur ShopPro ! Votre compte a été créé avec succès.
            </p>

            <div style="background-color: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 30px 0; border-radius: 4px;">
                <h3 style="color: #333333; margin-top: 0;">Vos informations de compte :</h3>
                <p style="color: #666666; margin: 10px 0;"><strong>Nom :</strong> {{ $user->nom }}</p>
                <p style="color: #666666; margin: 10px 0;"><strong>Email :</strong> {{ $user->email }}</p>
            </div>

            <p style="color: #666666; line-height: 1.6; margin-bottom: 30px;">
                Vous pouvez maintenant profiter de tous nos services :
            </p>

            <ul style="color: #666666; line-height: 1.8; margin-left: 20px;">
                <li>✅ Découvrir des milliers de produits</li>
                <li>✅ Ajouter des produits à vos favoris</li>
                <li>✅ Passer des commandes en toute sécurité</li>
                <li>✅ Suivre vos commandes en temps réel</li>
                <li>✅ Recevoir des offres exclusives</li>
            </ul>

            <div style="text-align: center; margin: 40px 0;">
                <a href="{{ config('app.url') }}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 15px 40px; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px;">
                    Commencer mes achats
                </a>
            </div>

            <p style="color: #666666; line-height: 1.6;">
                Si vous avez des questions, n'hésitez pas à contacter notre service client.
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