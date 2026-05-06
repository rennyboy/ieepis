# syntax=docker/dockerfile:1.6

# ---- Stage 1: Build front-end assets with Vite ----
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js postcss.config.js tailwind.config.js* ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---- Stage 2: PHP-FPM application ----
FROM php:8.4-fpm AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        zip \
        unzip \
        libicu-dev \
        libpq-dev \
        postgresql-client \
        libfcgi-bin \
        procps \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Production-tuned PHP config
RUN { \
        echo "memory_limit=512M"; \
        echo "upload_max_filesize=64M"; \
        echo "post_max_size=64M"; \
        echo "max_execution_time=300"; \
        echo "opcache.enable=1"; \
        echo "opcache.memory_consumption=192"; \
        echo "opcache.interned_strings_buffer=16"; \
        echo "opcache.max_accelerated_files=20000"; \
        echo "opcache.validate_timestamps=0"; \
    } > /usr/local/etc/php/conf.d/zz-production.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Install PHP deps with cached layer
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --optimize-autoloader \
        --no-scripts \
        --no-interaction \
        --prefer-dist

# Copy application source
COPY . .

# Drop in the built assets from the Node stage
COPY --from=assets /app/public/build ./public/build

# Bake the storage symlink so it exists in any image derived from this stage
RUN ln -sfn /var/www/storage/app/public /var/www/public/storage

# Finalize autoload (runs package:discover + filament:upgrade via post-autoload-dump)
RUN composer dump-autoload --optimize --no-dev

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# ---- Stage 3: Nginx web server (separate image, same source) ----
FROM nginx:stable-alpine AS web

# Static assets and the public directory (with the storage symlink baked in)
COPY --from=app /var/www/public /var/www/public
COPY docker/nginx/prod.conf /etc/nginx/conf.d/default.conf

# Allow nginx to follow the public/storage symlink into a mounted volume
RUN apk add --no-cache curl

EXPOSE 80
