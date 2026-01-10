#!/bin/bash

echo "🐳 Setting up Docker environment for Laravel Movie API..."

# Function to check if Docker is running
check_docker() {
    docker info > /dev/null 2>&1
    return $?
}

# Check if Docker is running
if ! check_docker; then
    echo "⚠️  Docker daemon is not running. Please ensure Docker Desktop is started."
    echo "   You can start it by running: open -a Docker"
    echo "   Waiting for Docker to start..."
    
    # Wait for Docker to start (max 2 minutes)
    count=0
    while ! check_docker && [ $count -lt 120 ]; do
        echo -n "."
        sleep 1
        count=$((count + 1))
    done
    echo ""
    
    if ! check_docker; then
        echo "❌ Docker failed to start. Please start Docker Desktop manually and try again."
        exit 1
    fi
fi

echo "✅ Docker is running!"

# Stop any existing containers
echo "🛑 Stopping existing containers..."
docker-compose down -v 2>/dev/null || true

# Copy Docker environment file
echo "📝 Setting up environment configuration..."
cp .env.docker .env || echo "⚠️ .env.docker not found, using existing .env"

# Build and start the containers
echo "🏗️  Building and starting containers..."
docker-compose up -d --build

# Wait for containers to be ready
echo "⏳ Waiting for services to be ready..."
sleep 10

# Check if MySQL is ready
echo "🔄 Waiting for MySQL to be ready..."
until docker-compose exec db mysqladmin ping -h"localhost" --silent; do
    echo -n "."
    sleep 2
done
echo ""

# Generate application key if needed
echo "🔑 Generating application key..."
docker-compose exec app php artisan key:generate --force

# Install dependencies
echo "📦 Installing Composer dependencies..."
docker-compose exec app composer install --optimize-autoloader

# Run migrations
echo "🗃️  Running database migrations..."
docker-compose exec app php artisan migrate --force

# Skip automatic seeding - manual seeding only
echo "🌱 Master data seeding skipped"
echo "💡 To seed data manually when needed:"
echo "   docker-compose exec app php artisan db:seed"
echo "   docker-compose exec app php artisan movies:seed --limit=50"

# Clear caches
echo "🧹 Clearing application caches..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Set proper permissions
echo "🔐 Setting proper permissions..."
docker-compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker-compose exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache

echo ""
echo "🎉 Setup complete! Your Laravel Movie API is now running:"
echo "   🌐 Application: http://localhost:8000"
echo "   🗄️  PhpMyAdmin: http://localhost:8081"
echo "   📊 Database: localhost:3306"
echo "   🔴 Redis: localhost:6379"
echo "   🎬 GraphQL Endpoint: http://localhost:8000/graphql"
echo ""
echo "📋 Database credentials:"
echo "   Database: movie_db"
echo "   Username: laravel"
echo "   Password: password"
echo "   Root password: root"
echo ""
echo "📊 Database is ready but empty"
echo ""
echo "🔧 Useful commands:"
echo "   View logs: docker-compose logs -f"
echo "   Stop services: docker-compose down"
echo "   Restart services: docker-compose restart"
echo "   Enter app container: docker-compose exec app bash"
echo ""
echo "🌱 Manual seeding commands:"
echo "   Seed users: docker-compose exec app php artisan db:seed"
echo "   Seed movies: docker-compose exec app php artisan movies:seed --limit=50"