#!/bin/bash

echo "🚀 Deploying IEEPIS Application..."

# Stop existing containers
echo "⏹️  Stopping existing containers..."
docker-compose down

# Copy environment file
echo "📝 Setting up environment..."
cp .env.docker .env

# Build containers
echo "🔨 Building Docker containers..."
docker-compose build --no-cache

# Start containers
echo "▶️  Starting containers..."
docker-compose up -d

# Wait for MySQL to be ready
echo "⏳ Waiting for database..."
sleep 10

# Generate app key
echo "🔑 Generating application key..."
docker-compose exec -T app php artisan key:generate

# Run migrations
echo "📊 Running migrations..."
docker-compose exec -T app php artisan migrate --force

# Clear and cache config
echo "🧹 Clearing caches..."
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan view:clear

echo "✨ Deployment complete!"
echo "🌐 Application available at: http://localhost:8080"
echo "📊 Database accessible at: localhost:3307"
echo ""
echo "📝 Create admin user:"
echo "   docker-compose exec app php artisan make:filament-user"
