# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1: Build front-end assets with Vite
# ---------------------------------------------------------------------------
FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ---------------------------------------------------------------------------
# Stage 2: Install PHP dependencies with Composer
# Pin PHP 8.2 to match runtime. composer:2 currently ships PHP 8.5, which
# rejects league/config -> nette/schema (^1.2, php 8.1-8.4). Spreadsheet also
# requires ext-gd / ext-zip during composer platform checks.
# ---------------------------------------------------------------------------
FROM php:8.2-cli-bookworm AS vendor

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libicu-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" zip intl gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy only what is needed to resolve/install dependencies for better caching.
COPY composer.json composer.lock ./
# App code is required because "files" autoload + package:discover run on install.
COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

# ---------------------------------------------------------------------------
# Stage 3: Final runtime image (PHP-FPM + Nginx + Supervisor)
# ---------------------------------------------------------------------------
FROM php:8.2-fpm-bookworm AS runtime

# System packages + PHP extensions (pdo_pgsql for PostgreSQL, others common to Laravel).
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        libpq-dev \
        libzip-dev \
        libicu-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        zip \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        bcmath \
        intl \
        zip \
        gd \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Production PHP + OPcache settings.
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf

# Nginx + Supervisor config.
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

# Application source.
COPY . .

# Vendor + built assets from previous stages.
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# Entrypoint runs migrations / caching before starting services.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Laravel needs writable storage + cache dirs; www-data runs php-fpm.
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
