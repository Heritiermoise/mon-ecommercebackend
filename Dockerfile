FROM php:8.3-apache

# Variables d'environnement
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV COMPOSER_ALLOW_SUPERUSER=1

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    pkg-config \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Activer Apache mod_rewrite et headers
RUN a2enmod rewrite headers

# Configurer Apache pour pointer vers /public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configuration Apache pour Laravel (AllowOverride)
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier d'abord composer.json pour le cache Docker
COPY composer.json composer.lock ./

# Installer les dépendances
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader --no-interaction

# Copier le reste du projet
COPY . .

# Générer l'autoloader optimisé
RUN composer dump-autoload --optimize --classmap-authoritative

# Créer les dossiers nécessaires
RUN mkdir -p storage/app/public \
    && mkdir -p storage/framework/cache/data \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

# Permissions initiales
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Copier le script d'entrée
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Exposer le port 80 (IMPORTANT pour Render)
EXPOSE 80

# Point d'entrée
ENTRYPOINT ["/entrypoint.sh"]