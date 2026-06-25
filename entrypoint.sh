#!/bin/bash

# NE PAS utiliser set -e pour que Apache démarre même si les migrations échouent

echo "============================================"
echo "🚀 Démarrage de ShopPro Backend"
echo "============================================"

# ============================================
# 1. GÉNÉRER APP_KEY SI ABSENT
# ============================================
if [ -z "$APP_KEY" ]; then
    echo "🔑 Génération de APP_KEY..."
    php artisan key:generate --force --no-interaction 2>&1 || echo "⚠️ Impossible de générer APP_KEY"
fi

# ============================================
# 2. CRÉER LE FICHIER .ENV DEPUIS LES VARIABLES
# ============================================
echo "📝 Configuration de .env..."

# Créer .env avec les variables d'environnement Render
cat > .env <<EOF
APP_NAME=${APP_NAME:-ShopPro}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}
APP_TIMEZONE=${APP_TIMEZONE:-UTC}

LOG_CHANNEL=${LOG_CHANNEL:-errorlog}
LOG_LEVEL=${LOG_LEVEL:-error}

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-}
DB_USERNAME=${DB_USERNAME:-}
DB_PASSWORD=${DB_PASSWORD:-}
DB_MYSQL_ATTR_SSL_CA=${DB_MYSQL_ATTR_SSL_CA:-}

CACHE_DRIVER=${CACHE_DRIVER:-file}
SESSION_DRIVER=${SESSION_DRIVER:-file}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}

JWT_SECRET=${JWT_SECRET:-}
JWT_TTL=${JWT_TTL:-60}

MAIL_MAILER=${MAIL_MAILER:-smtp}
MAIL_HOST=${MAIL_HOST:-}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME:-}
MAIL_PASSWORD=${MAIL_PASSWORD:-}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-noreply@shoppro.com}
MAIL_FROM_NAME="${MAIL_FROM_NAME:-ShopPro}"

MAISHAPAY_MERCHANT_ID=${MAISHAPAY_MERCHANT_ID:-}
MAISHAPAY_PUBLIC_KEY=${MAISHAPAY_PUBLIC_KEY:-}
MAISHAPAY_SECRET_KEY=${MAISHAPAY_SECRET_KEY:-}
MAISHAPAY_API_URL=${MAISHAPAY_API_URL:-https://marchand.maishapay.online}

FRONTEND_URL=${FRONTEND_URL:-}
EOF

echo "✓ Fichier .env créé"

# ============================================
# 3. AFFICHER L'ÉTAT DE LA CONFIGURATION
# ============================================
echo ""
echo "🔍 État de la configuration :"
echo "   APP_KEY: $([ -n "$APP_KEY" ] && echo '✓ Défini' || echo '❌ MANQUANT')"
echo "   DB_HOST: ${DB_HOST:-(vide)}"
echo "   DB_PORT: ${DB_PORT:-3306}"
echo "   DB_DATABASE: ${DB_DATABASE:-(vide)}"
echo "   DB_USERNAME: ${DB_USERNAME:-(vide)}"
echo "   JWT_SECRET: $([ -n "$JWT_SECRET" ] && echo '✓ Défini' || echo '❌ MANQUANT')"
echo ""

# ============================================
# 4. OPTIMISER LARAVEL
# ============================================
echo "⚡ Optimisation de Laravel..."
php artisan config:cache 2>&1 || echo "⚠️ Erreur config:cache"
php artisan route:cache 2>&1 || echo "⚠️ Erreur route:cache"
php artisan view:cache 2>&1 || echo "⚠️ Erreur view:cache"

# ============================================
# 5. EXÉCUTER LES MIGRATIONS (AVEC GESTION D'ERREURS)
# ============================================
if [ -n "$DB_HOST" ] && [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ]; then
    echo "🗄️ Exécution des migrations..."
    if php artisan migrate --force --no-interaction 2>&1; then
        echo "✓ Migrations exécutées avec succès"
    else
        echo "⚠️ Erreur lors des migrations - l'application continuera quand même"
        echo "   Vérifiez que les variables DB sont correctes dans Render"
    fi
else
    echo "⚠️ Variables DB manquantes - migrations ignorées"
    echo "   Pour activer la base de données, configurez dans Render :"
    echo "   - DB_HOST"
    echo "   - DB_DATABASE"
    echo "   - DB_USERNAME"
    echo "   - DB_PASSWORD"
fi

# ============================================
# 6. LIEN STORAGE
# ============================================
php artisan storage:link 2>/dev/null || echo "⚠️ storage:link ignoré"

# ============================================
# 7. PERMISSIONS
# ============================================
echo "🔐 Configuration des permissions..."
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# ============================================
# 8. DÉMARRER APACHE
# ============================================
echo ""
echo "============================================"
echo "✅ Configuration terminée"
echo "🌐 Démarrage d'Apache sur le port 80..."
echo "============================================"
echo ""

# Démarrer Apache (cette commande ne retourne jamais)
exec apache2-foreground