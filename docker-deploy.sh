#!/bin/bash

echo "🚀 Deploying IEEPIS Application..."

# Stop existing containers
echo "⏹️  Stopping existing containers..."
docker compose down

# Copy environment file
echo "📝 Setting up environment..."
cp .env.docker .env

# Build containers
echo "🔨 Building Docker containers..."
docker compose build --no-cache

# Start containers
echo "▶️  Starting containers..."
docker compose up -d

# Wait for MySQL to be ready
echo "⏳ Waiting for database..."
sleep 15

# Generate app key
echo "🔑 Generating application key..."
docker compose exec -T app php artisan key:generate

# Run migrations (including cache table)
echo "📊 Running migrations..."
docker compose exec -T app php artisan migrate --force

# Create cache table
echo "📊 Creating cache table..."
docker compose exec -T app php artisan cache:table 2>/dev/null || true
docker compose exec -T app php artisan migrate --force

# Clear and cache config (skip cache:clear to avoid the error)
echo "🧹 Clearing caches..."
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan route:clear
docker compose exec -T app php artisan view:clear

# Cache config for production
echo "⚡ Caching configuration..."
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

echo "✨ Deployment complete!"
echo "🌐 Application available at: http://localhost:8080"
echo "📊 Database accessible at: localhost:3307"
echo ""
echo "📝 Create admin user:"
echo "   docker compose exec app php artisan make:filament-user"
