#!/bin/bash
set -e

echo "Starting Laravel application initialization..."

# Function to check if MySQL is ready
wait_for_db() {
    local host=$1
    local port=$2
    local user=$3
    local password=$4

    echo "Waiting for MySQL at $host:$port..."

    for i in {1..30}; do
        if mysqladmin ping -h"$host" -u"$user" -p"$password" --silent 2>/dev/null; then
            echo "MySQL is ready!"
            return 0
        fi
        echo "Attempt $i/30: MySQL not ready yet, waiting..."
        sleep 1
    done

    echo "MySQL failed to start within timeout"
    return 1
}

# Wait for database to be ready
wait_for_db "${DB_HOST}" "${DB_PORT}" "${DB_USERNAME}" "${DB_PASSWORD}"

# Clear existing caches
echo "Clearing application cache..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Run migrations
echo "Running database migrations..."
php artisan migrate --force 2>/dev/null || {
    echo "Migration warning, continuing..."
}

# Optimize application for production
echo "Optimizing application..."
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

echo "Laravel initialization complete. Starting application services..."

# Start supervisord to manage Nginx and PHP-FPM
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
