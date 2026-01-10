.PHONY: help build up down restart logs shell mysql redis clean install migrate seed fresh test setup seed-movies

# Default target
help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# Setup commands
setup: ## Complete Docker setup with auto-migration and seeding
	./docker-setup.sh

# Docker commands
build: ## Build and start all containers
	docker-compose up -d --build

up: ## Start all containers
	docker-compose up -d

down: ## Stop all containers
	docker-compose down

restart: ## Restart all containers
	docker-compose restart

logs: ## Show logs from all containers
	docker-compose logs -f

shell: ## Access Laravel app container shell
	docker-compose exec app bash

mysql: ## Access MySQL shell
	docker-compose exec db mysql -u laravel -ppassword movie_db

redis: ## Access Redis CLI
	docker-compose exec redis redis-cli

# Laravel commands
install: ## Install Composer dependencies
	docker-compose exec app composer install --optimize-autoloader

migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

seed: ## Run database seeders
	docker-compose exec app php artisan db:seed

seed-movies: ## Seed movies from OMDb API (specify limit: make seed-movies LIMIT=50)
	docker-compose exec app php artisan movies:seed --limit=${LIMIT:-100}

fresh: ## Fresh migration with seeding
	docker-compose exec app php artisan migrate:fresh --seed

fresh-with-movies: ## Fresh migration with seeding including movies
	docker-compose exec app php artisan migrate:fresh --seed
	docker-compose exec app php artisan movies:seed --limit=100

test: ## Run tests
	docker-compose exec app php artisan test

key: ## Generate application key
	docker-compose exec app php artisan key:generate

cache-clear: ## Clear all caches
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

# Utility commands
clean: ## Remove all containers, volumes, and images
	docker-compose down -v
	docker system prune -f

permissions: ## Fix storage and cache permissions
	docker-compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
	docker-compose exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache

setup: ## Complete setup process
	./docker-setup.sh