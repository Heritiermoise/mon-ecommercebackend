#!/bin/bash
set -u

echo "Démarrage de ShopPro Backend..."

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY manquant, génération d'une clé temporaire..."
    php artisan key:generate --force || true
fi

SSL_CA_PATH="${DB_MYSQL_ATTR_SSL_CA:-${MYSQL_ATTR_SSL_CA:-cert/ca.pem}}"
if [ -f "/var/www/html/${SSL_CA_PATH}" ]; then
    echo "Certificat CA trouvé: /var/www/html/${SSL_CA_PATH}"
else
    echo "Certificat CA introuvable: /var/www/html/${SSL_CA_PATH}"
fi

# Exécuter les migrations
echo "Exécution des migrations..."
php artisan migrate --force || echo "Migrations ignorées: base indisponible au démarrage"

# Optimiser Laravel
echo "Optimisation de Laravel..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Créer le lien storage si nécessaire
php artisan storage:link || true

# Donner les permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Démarrage d'Apache..."
apache2-foreground