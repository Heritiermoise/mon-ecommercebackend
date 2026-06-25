#!/bin/bash
set -e

echo "🚀 Démarrage de ShopPro Backend..."

# Générer APP_KEY si absent
if [ -z "$APP_KEY" ]; then
    echo "🔑 Génération de APP_KEY..."
    php artisan key:generate --force
fi

# Créer le fichier .env depuis les variables d'environnement si nécessaire
if [ ! -f .env ] || [ ! -s .env ]; then
    echo "📝 Création du fichier .env..."
    cat > .env <<EOF
APP_NAME=${APP_NAME:-ShopPro}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}

LOG_CHANNEL=${LOG_CHANNEL:-errorlog}
LOG_LEVEL=${LOG_LEVEL:-error}

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
DB_MYSQL_ATTR_SSL_CA=${DB_MYSQL_ATTR_SSL_CA:-}

CACHE_DRIVER=${CACHE_DRIVER:-file}
SESSION_DRIVER=${SESSION_DRIVER:-file}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}

JWT_SECRET=${JWT_SECRET}
JWT_TTL=${JWT_TTL:-60}

MAIL_MAILER=${MAIL_MAILER:-smtp}
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME}
MAIL_PASSWORD=${MAIL_PASSWORD}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-noreply@shoppro.com}
MAIL_FROM_NAME="${MAIL_FROM_NAME:-ShopPro}"

MAISHAPAY_MERCHANT_ID=${MAISHAPAY_MERCHANT_ID}
MAISHAPAY_PUBLIC_KEY=${MAISHAPAY_PUBLIC_KEY}
MAISHAPAY_SECRET_KEY=${MAISHAPAY_SECRET_KEY}
MAISHAPAY_API_URL=${MAISHAPAY_API_URL:-https://marchand.maishapay.online}

FRONTEND_URL=${FRONTEND_URL}
EOF
fi

# Optimiser Laravel
echo "⚡ Optimisation de Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Exécuter les migrations
echo "🗄️ Exécution des migrations..."
php artisan migrate --force --no-interaction

# Créer le lien storage
php artisan storage:link 2>/dev/null || true

# Permissions finales
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "✅ Démarrage terminé!"
echo "🌐 Application disponible sur le port 80"

# Exécuter la commande CMD
exec "$@"