#!/bin/bash

echo "============================================"
echo "🚀 Démarrage de ShopPro Backend"
echo "============================================"

SSL_CA_TARGET="/var/www/html/storage/app/tidb-ca.pem"

if [ -n "${DB_SSL_CA_PEM:-}" ]; then
    echo "🧾 Écriture du certificat SSL MySQL depuis DB_SSL_CA_PEM..."
    printf '%s\n' "$DB_SSL_CA_PEM" > "$SSL_CA_TARGET"
    export DB_SSL_CA_PATH="$SSL_CA_TARGET"
    export DB_MYSQL_ATTR_SSL_CA="$SSL_CA_TARGET"
    export MYSQL_ATTR_SSL_CA="$SSL_CA_TARGET"
    echo "✓ Certificat SSL enregistré dans $SSL_CA_TARGET"
fi

# ============================================
# 1. CRÉER LE FICHIER .ENV DEPUIS LES VARIABLES RENDER
# ============================================
echo "📝 Création du fichier .env depuis les variables Render..."

cat > /var/www/html/.env <<EOF
APP_NAME=${APP_NAME:-ShopPro}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY:-base64:cYBhg48EWx31z3+VlSp8SzEllcXY70xThT9DyFFjjU0=}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-https://shoppro-backend.onrender.com}
APP_TIMEZONE=${APP_TIMEZONE:-UTC}

LOG_CHANNEL=${LOG_CHANNEL:-errorlog}
LOG_LEVEL=${LOG_LEVEL:-error}

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-gateway01.us-east-1.prod.aws.tidbcloud.com}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-ecommerce}
DB_USERNAME=${DB_USERNAME:-2nm7aoqJDMvP7m2.root}
DB_PASSWORD=${DB_PASSWORD:-remplace-par-ton-mot-de-passe-tidb}
DB_SSLMODE=${DB_SSLMODE:-verify-ca}
DB_SSL_CA_PATH=${DB_SSL_CA_PATH:-${DB_MYSQL_ATTR_SSL_CA:-${MYSQL_ATTR_SSL_CA:-cert/isrgrootx1 (1).pem}}}
DB_MYSQL_ATTR_SSL_CA=${DB_MYSQL_ATTR_SSL_CA:-${MYSQL_ATTR_SSL_CA:-${DB_SSL_CA_PATH:-cert/isrgrootx1 (1).pem}}}
MYSQL_ATTR_SSL_CA=${MYSQL_ATTR_SSL_CA:-${DB_MYSQL_ATTR_SSL_CA:-${DB_SSL_CA_PATH:-cert/isrgrootx1 (1).pem}}}

CACHE_DRIVER=${CACHE_DRIVER:-file}
SESSION_DRIVER=${SESSION_DRIVER:-file}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}

JWT_SECRET=${JWT_SECRET:-remplace-par-un-secret-long-et-aleatoire}
JWT_TTL=${JWT_TTL:-60}

MAIL_MAILER=${MAIL_MAILER:-smtp}
MAIL_HOST=${MAIL_HOST:-smtp-relay.brevo.com}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME:-ac18d5001@smtp-brevo.com}
MAIL_PASSWORD=${MAIL_PASSWORD:-xsmtpsib-cf4f596da665d1f13f8a891ef0d4fbd56c8076bce628b86b44b50cf851bd6344-DFOlzHVGW5C3YH6x}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-noreply@shoppro.com}
MAIL_FROM_NAME="${MAIL_FROM_NAME:-ShopPro}"

MAISHAPAY_MERCHANT_ID=${MAISHAPAY_MERCHANT_ID:-002332}
MAISHAPAY_PUBLIC_KEY=${MAISHAPAY_PUBLIC_KEY:-"MP-SBPK-m2hIWlYgX5zpf8r0jXMq5kk1kcMHUI5t0OCMQ/.$9Hh1uiYYva92zH$9XcwyMunqh3oNQjvSTXgaek$gR3T.mR.ctHU13lyypOci4G2djn$0gmu1$oLVNMq$"}
MAISHAPAY_SECRET_KEY=${MAISHAPAY_SECRET_KEY:-"MP-SBSK-YUdvYU3XadGyiT2BlK0E1Xp08$3iFiWi/znF6$V.OaVHcM$U4CIcdu0xYPFoV1jxNe3kYAotsF60FR3vzCB$H.O7ny2zbPmO5wFTJN$t52AJFTwerv$uJN92"}
MAISHAPAY_API_URL=${MAISHAPAY_API_URL:-https://marchand.maishapay.online}

FRONTEND_URL=${FRONTEND_URL:-https://mon-ecommercefrontend.vercel.app}
EOF

echo "✓ Fichier .env créé"

# ============================================
# 2. AFFICHER L'ÉTAT DE LA CONFIGURATION
# ============================================
echo ""
echo "🔍 État de la configuration :"
echo "   APP_KEY: $([ -n "${APP_KEY:-}" ] && echo '✓ Défini' || echo '❌ MANQUANT')"
echo "   DB_HOST: ${DB_HOST:-gateway01.us-east-1.prod.aws.tidbcloud.com}"
echo "   DB_PORT: ${DB_PORT:-4000}"
echo "   DB_DATABASE: ${DB_DATABASE:-ecommerce}"
echo "   DB_USERNAME: ${DB_USERNAME:-2nm7aoqJDMvP7m2.root}"
echo "   DB_SSLMODE: ${DB_SSLMODE:-verify-ca}"
echo "   DB_MYSQL_ATTR_SSL_CA: ${DB_MYSQL_ATTR_SSL_CA:-cert/isrgrootx1 (1).pem}"
echo "   MYSQL_ATTR_SSL_CA: ${MYSQL_ATTR_SSL_CA:-cert/isrgrootx1 (1).pem}"
echo "   JWT_SECRET: $([ -n "${JWT_SECRET:-}" ] && echo '✓ Défini' || echo '❌ MANQUANT')"
echo ""

SSL_CA_PATH="/var/www/html/${DB_SSL_CA_PATH:-${DB_MYSQL_ATTR_SSL_CA:-${MYSQL_ATTR_SSL_CA:-cert/isrgrootx1 (1).pem}}}"
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