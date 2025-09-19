# Laravel URL Shortener API - Docker Commands

.PHONY: help build up down restart logs shell composer install migrate seed test clean

# Default target
help: ## Show this help message
	@echo "Laravel URL Shortener API - Available Commands:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Docker commands
build: ## Build the Docker containers
	docker-compose build

up: ## Start all services
	docker-compose up -d

down: ## Stop all services
	docker-compose down

run-local: ## Start the whole application (build, up, install, migrate)
	@echo "Starting Laravel URL Shortener API..."
	@make build
	@make up
	@echo "⏳ Waiting for services to be ready..."
	@sleep 15
	@make install
	@make key
	@make migrate
	@echo ""
	@echo "Application is running!"
	@echo "Application: http://localhost:8080"
	@echo "PHPMyAdmin: http://localhost:8081"
	@echo "Redis: localhost:6380"
	@echo "MySQL: localhost:3307"

down-local: ## Shut down the whole application
	@echo "Shutting down Laravel URL Shortener API..."
	@make down
	@echo "Application stopped successfully"

restart: ## Restart all services
	docker-compose restart

logs: ## Show logs for all services
	docker-compose logs -f

logs-app: ## Show logs for app service only
	docker-compose logs -f app

logs-nginx: ## Show logs for nginx service only
	docker-compose logs -f nginx

logs-mysql: ## Show logs for mysql service only
	docker-compose logs -f mysql

logs-redis: ## Show logs for redis service only
	docker-compose logs -f redis

# Application commands
shell: ## Access the app container shell
	docker-compose exec app bash

composer: ## Run composer commands (usage: make composer CMD="install")
	docker-compose exec app composer $(CMD)

install: ## Install PHP dependencies
	docker-compose exec app composer install

migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

migrate-fresh: ## Fresh migration with seeding
	docker-compose exec app php artisan migrate:fresh --seed

seed: ## Run database seeders
	docker-compose exec app php artisan db:seed

key: ## Generate application key
	docker-compose exec app php artisan key:generate

cache: ## Clear application cache
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

test: ## Run PHPUnit tests
	docker-compose exec app php artisan test

# Database commands
db-shell: ## Access MySQL shell
	docker-compose exec mysql mysql -u root -p

redis-shell: ## Access Redis shell
	docker-compose exec redis redis-cli

# Development commands
dev: ## Start development environment
	docker-compose up -d
	@echo "Waiting for services to be ready..."
	@sleep 10
	@make install
	@make key
	@make migrate
	@echo "Development environment ready!"
	@echo "Application: http://localhost:8080"
	@echo "PHPMyAdmin: http://localhost:8081"

# Cleanup commands
clean: ## Remove all containers and volumes
	docker-compose down -v
	docker system prune -f

clean-all: ## Remove everything including images
	docker-compose down -v --rmi all
	docker system prune -af

# Status commands
status: ## Show status of all services
	docker-compose ps

# Quick setup for new developers
setup: ## Complete setup for new developers
	@echo "Setting up Laravel URL Shortener API..."
	@make build
	@make up
	@echo "Waiting for services to be ready..."
	@sleep 15
	@make install
	@make key
	@make migrate
	@echo ""
	@echo "Setup complete!"
	@echo "Application: http://localhost:8080"
	@echo "PHPMyAdmin: http://localhost:8081"
	@echo "Redis: localhost:6380"
	@echo "MySQL: localhost:3307"
