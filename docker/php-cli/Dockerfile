FROM php:8.4-fpm-alpine

# Install system dependencies and PHP extensions for Artisan commands
RUN apk add --no-cache \
    git \
    curl \
    libxml2-dev \
    libzip-dev \
    libpng-dev \
    jpeg-dev \
    icu-dev \
    mysql-client \
    bash \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/* /usr/share/doc/* \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        xml \
        dom

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory inside the container
WORKDIR /var/www

# Set proper permissions for the application directory
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Keep container running for CLI usage
CMD ["tail", "-f", "/dev/null"]
