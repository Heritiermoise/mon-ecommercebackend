<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopPro - API Backend</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .container {
            text-align: center;
            padding: 40px;
            max-width: 600px;
        }
        h1 { font-size: 3em; margin-bottom: 20px; }
        p { font-size: 1.2em; margin-bottom: 30px; opacity: 0.9; }
        .status {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            backdrop-filter: blur(10px);
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .status-item:last-child { border-bottom: none; }
        .badge {
            background: #10b981;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
        }
        .links { margin-top: 30px; }
        .links a {
            color: #fff;
            text-decoration: none;
            margin: 0 15px;
            padding: 10px 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 25px;
            transition: all 0.3s;
        }
        .links a:hover {
            background: rgba(255,255,255,0.2);
            border-color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛍️ ShopPro API</h1>
        <p>Backend Laravel déployé avec succès sur Render</p>
        
        <div class="status">
            <div class="status-item">
                <span>Statut</span>
                <span class="badge">✓ En ligne</span>
            </div>
            <div class="status-item">
                <span>PHP</span>
                <span>{{ PHP_VERSION }}</span>
            </div>
            <div class="status-item">
                <span>Laravel</span>
                <span>{{ app()->version() }}</span>
            </div>
        </div>
        
        <div class="links">
            <a href="/health">Health Check</a>
            <a href="/api">API Info</a>
            <a href="/api/products">Products</a>
        </div>
        
        <p style="margin-top: 40px; font-size: 0.9em; opacity: 0.7;">
            © {{ date('Y') }} ShopPro. Tous droits réservés.
        </p>
    </div>
</body>
</html>