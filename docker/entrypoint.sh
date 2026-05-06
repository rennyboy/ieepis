#!/bin/bash
set -e

echo "Starting Laravel application initialization..."

# Function to check if Postgres is ready
wait_for_db() {
    local host=$1
    local port=$2
    local user=$3
    local db=$4

    echo "Waiting for PostgreSQL at $host:$port..."

    for i in {1..30}; do
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

# Wait for database to be ready
wait_for_db "${DB_HOST}" "${DB_PORT}" "${DB_USERNAME}" "${DB_DATABASE}"

# Run optimizations only in the main app container (not worker/scheduler)
if [ "$1" = "php-fpm" ]; then
    # Clear existing caches
    echo "Clearing application cache..."
    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan view:clear || true

    # Run migrations
    echo "Running database migrations..."
    php artisan migrate --force || {
        echo "Migration failed!"
        exit 1
    }

    # Ensure public storage symlink exists
    echo "Linking public storage..."
    php artisan storage:link --force || true

    # Optimize application for production
    echo "Optimizing application..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true

    # Set proper permissions
    echo "Setting permissions..."
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
fi

echo "Laravel initialization complete. Starting: $@"

exec "$@"
