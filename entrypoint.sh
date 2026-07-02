#!/bin/bash

echo "============================================"
echo "🚀 Démarrage de ShopPro Backend"
echo "============================================"

# ============================================
# 1. CRÉER LE FICHIER .ENV DEPUIS LES VARIABLES RENDER
# ============================================
echo "📝 Création du fichier .env depuis les variables Render..."

cat > /var/www/html/.env <<EOF
APP_NAME=${APP_NAME:-ShopPro}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}
APP_TIMEZONE=${APP_TIMEZONE:-UTC}

LOG_CHANNEL=${LOG_CHANNEL:-errorlog}
LOG_LEVEL=${LOG_LEVEL:-error}

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
MYSQL_ATTR_SSL_CA=${MYSQL_ATTR_SSL_CA:-${DB_MYSQL_ATTR_SSL_CA:-cert/ca.pem}}
DB_MYSQL_ATTR_SSL_CA=${DB_MYSQL_ATTR_SSL_CA:-${MYSQL_ATTR_SSL_CA:-cert/ca.pem}}

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

echo "✓ Fichier .env créé"

# ============================================
# 2. AFFICHER L'ÉTAT DE LA CONFIGURATION
# ============================================
echo ""
echo "🔍 État de la configuration :"
echo "   APP_KEY: $([ -n "$APP_KEY" ] && echo '✓ Défini' || echo '❌ MANQUANT')"
echo "   DB_HOST: ${DB_HOST:-(vide)}"
echo "   DB_PORT: ${DB_PORT:-3306}"
echo "   DB_DATABASE: ${DB_DATABASE:-(vide)}"
echo "   DB_USERNAME: ${DB_USERNAME:-(vide)}"
echo "   MYSQL_ATTR_SSL_CA: ${MYSQL_ATTR_SSL_CA:-cert/ca.pem}"
echo "   JWT_SECRET: $([ -n "$JWT_SECRET" ] && echo '✓ Défini' || echo '❌ MANQUANT')"
echo ""

SSL_CA_PATH="/var/www/html/${MYSQL_ATTR_SSL_CA:-cert/ca.pem}"
if [ -f "$SSL_CA_PATH" ]; then
    echo "✓ Certificat SSL MySQL trouvé: $SSL_CA_PATH"
else
    echo "❌ Certificat SSL MySQL introuvable: $SSL_CA_PATH"
fi
echo ""

# ============================================
# 3. OPTIMISER LARAVEL
# ============================================
echo "⚡ Optimisation de Laravel..."
php artisan config:cache 2>&1 || echo "⚠️ Erreur config:cache"
php artisan route:cache 2>&1 || echo "⚠️ Erreur route:cache"
php artisan view:cache 2>&1 || echo "⚠️ Erreur view:cache"

# ============================================
# 4. EXÉCUTER LES MIGRATIONS AUTOMATIQUEMENT
# ============================================
if [ -n "$DB_HOST" ] && [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ]; then
    echo "🗄️ Exécution automatique des migrations..."
    
    # Attendre que la DB soit prête
    sleep 3
    
    if php artisan migrate --force --no-interaction 2>&1; then
        echo "✓ Migrations exécutées avec succès"
    else
        echo "⚠️ Erreur lors des migrations - l'application continuera quand même"
    fi
    
    # Créer le lien storage
    php artisan storage:link 2>/dev/null || echo "⚠️ storage:link ignoré"
else
    echo "⚠️ Variables DB manquantes - migrations ignorées"
    echo "   Configurez DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD sur Render"
fi

# ============================================
# 5. PERMISSIONS
# ============================================
echo "🔐 Configuration des permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# ============================================
# 6. DÉMARRER APACHE
# ============================================
echo ""
echo "============================================"
echo "✅ Configuration terminée"
echo "🌐 Démarrage d'Apache sur le port 80..."
echo "============================================"
echo ""

exec apache2-foreground