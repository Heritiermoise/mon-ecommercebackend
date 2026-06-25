#!/bin/bash
set -e

echo "Démarrage de ShopPro Backend..."

# Générer la clé d'application si elle n'existe pas
if [ -z "$APP_KEY" ]; then
    echo "Génération de APP_KEY..."
    php artisan key:generate --force
fi

# Exécuter les migrations
echo "Exécution des migrations..."
php artisan migrate --force

# Optimiser Laravel
echo "Optimisation de Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Créer le lien storage si nécessaire
php artisan storage:link || true

# Donner les permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Démarrage d'Apache..."
apache2-foreground