#!/bin/bash
set -e

echo "Starting Laravel application initialization..."

wait_for_db() {
    local host=$1
    local port=$2
    local user=$3
    local db=$4

    echo "Waiting for PostgreSQL at $host:$port..."

    for i in $(seq 1 30); do
        if pg_isready -h "$host" -p "$port" -U "$user" -d "$db" >/dev/null 2>&1; then
            echo "PostgreSQL is ready!"
            return 0
        fi
        echo "Attempt $i/30: PostgreSQL not ready yet, waiting..."
        sleep 1
    done

    echo "PostgreSQL failed to start within timeout"
    return 1
}

wait_for_db "${DB_HOST}" "${DB_PORT}" "${DB_USERNAME}" "${DB_DATABASE}"

# Only the primary php-fpm container runs migrations + caches.
# Worker and scheduler reuse the same image but skip these steps.
if [ "$1" = "php-fpm" ]; then
    echo "Clearing application cache..."
    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan view:clear || true
    php artisan route:clear || true
    php -r 'opcache_reset();' 2>/dev/null || true

    echo "Resetting Spatie permission cache..."
    php artisan permission:cache-reset || true

    echo "Running database migrations..."
    php artisan migrate --force || {
        echo "Migration failed!"
        exit 1
    }

    echo "Linking public storage..."
    php artisan storage:link --force || true

    echo "Optimizing application..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    php artisan event:cache || true

    echo "Caching Filament components..."
    php artisan filament:cache-components || true
    php artisan icons:cache || true

    echo "Setting permissions..."
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
fi

echo "Laravel initialization complete. Starting: $*"

exec "$@"
