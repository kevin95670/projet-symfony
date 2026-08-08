FROM php:8.2-apache

# Un seul MPM autorisé : on désactive les autres avant d'activer prefork
RUN a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork rewrite

RUN ls -la /etc/apache2/mods-enabled/ | grep mpm

# Dépendances système (avant les extensions PHP qui en dépendent)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libicu-dev \
    mariadb-client \
    curl \
    git \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP
RUN docker-php-ext-install pdo_mysql zip intl opcache

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash
ENV PATH="/root/.symfony5/bin:$PATH"

# Configuration Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Code de l'application + dépendances
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Permissions pour Symfony (cache et logs)
RUN chown -R www-data:www-data /var/www/html/var