FROM php:8.3-apache

# Variables d'environnement
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV COMPOSER_ALLOW_SUPERUSER=1

# Installer les dÃ©pendances systÃ¨me
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

# Activer Apache mod_rewrite
RUN a2enmod rewrite headers

# Configurer Apache pour pointer vers /public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configuration Apache pour Laravel
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# DÃ©finir le rÃ©pertoire de travail
WORKDIR /var/www/html

# Copier d'abord composer.json pour le cache
COPY composer.json composer.lock ./

# Installer les dÃ©pendances (cache layer)
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

# Copier le reste du projet
COPY . .

# GÃ©nÃ©rer l'autoloader optimisÃ©
RUN composer dump-autoload --optimize --classmap-authoritative

# CrÃ©er les fichiers nÃ©cessaires
RUN touch .env \
    && php artisan storage:link || true \
    && mkdir -p storage/app/public \
    && mkdir -p storage/framework/cache/data \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Script d'entrÃ©e
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]