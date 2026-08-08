FROM php:8.2-apache

RUN a2enmod rewrite

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libicu-dev \
    mariadb-client \
    curl \
    git \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql zip intl opcache

# composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# symfony cli
RUN curl -sS https://get.symfony.com/cli/installer | bash
ENV PATH="/root/.symfony5/bin:$PATH"

COPY apache-config.conf /etc/apache2/sites-available/000-default.conf