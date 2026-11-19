# syntax=docker/dockerfile:1

# Stage 1: Install PHP production dependencies
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# Stage 2: Build Vite + Tailwind CSS production assets
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# Stage 3: Production PHP 8.4-FPM + Nginx runtime (no Apache / no MPM conflicts)
FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pdo_mysql \
        zip \
    && rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP limits for image/document uploads (5 MB app limit) and OPcache
RUN printf '%s\n' \
    'upload_max_filesize=10M' \
    'post_max_size=25M' \
    'memory_limit=256M' \
    'max_execution_time=60' \
    'opcache.enable=1' \
    'opcache.validate_timestamps=0' \
    > /usr/local/etc/php/conf.d/99-sinar-connect.ini

# Ensure PHP-FPM preserves Railway environment variables and logs worker output
RUN printf '%s\n' \
    '[www]' \
    'clear_env = no' \
    'catch_workers_output = yes' \
    'decorate_workers_output = no' \
    > /usr/local/etc/php-fpm.d/zz-sinar-connect.conf

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN rm -f public/hot \
    && mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
