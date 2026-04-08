# IEEPIS — Makefile
# Universal command shortcuts — works in any terminal, any IDE
# Usage: make <command>   (e.g. make up, make test, make pint)

SAIL = vendor/bin/sail
ARTISAN = $(SAIL) artisan

.PHONY: help up stop dev build test test-filter migrate seed fresh clear \
        pint tinker routes logs qrcodes shell db-shell

## ── Help ──────────────────────────────────────────────────────────────────
help: ## Show all available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

## ── Docker / Sail ─────────────────────────────────────────────────────────
up: ## Start Laravel Sail (Docker)
	$(SAIL) up -d

stop: ## Stop Laravel Sail
	$(SAIL) stop

restart: ## Restart Laravel Sail
	$(SAIL) stop && $(SAIL) up -d

shell: ## Open bash shell in the Sail container
	$(SAIL) shell

db-shell: ## Open MySQL shell
	$(SAIL) mysql

## ── Asset Building ────────────────────────────────────────────────────────
dev: ## Start Vite dev server (hot reload)
	$(SAIL) npm run dev

build: ## Build production assets
	$(SAIL) npm run build

## ── Testing ───────────────────────────────────────────────────────────────
test: ## Run all PHPUnit tests
	$(ARTISAN) test --compact

test-filter: ## Run specific test by name — usage: make test-filter f=TestName
	$(ARTISAN) test --compact --filter=$(f)

## ── Database ──────────────────────────────────────────────────────────────
migrate: ## Run pending migrations
	$(ARTISAN) migrate

migrate-fresh: ## Drop all tables and re-run all migrations
	$(ARTISAN) migrate:fresh

fresh: ## Fresh migrate + seed (full reset)
	$(ARTISAN) migrate:fresh --seed

seed: ## Run database seeders
	$(ARTISAN) db:seed

migrate-status: ## Show migration status
	$(ARTISAN) migrate:status

## ── Code Quality ──────────────────────────────────────────────────────────
pint: ## Fix PHP code style with Laravel Pint
	$(SAIL) bin pint --dirty --format agent

pint-all: ## Fix ALL PHP files with Pint
	$(SAIL) bin pint --format agent

## ── Laravel Cache ─────────────────────────────────────────────────────────
clear: ## Clear all Laravel caches
	$(ARTISAN) optimize:clear

optimize: ## Cache config/routes/views (production)
	$(ARTISAN) optimize

## ── Artisan Utilities ─────────────────────────────────────────────────────
tinker: ## Open Laravel Tinker REPL
	$(ARTISAN) tinker

routes: ## List all registered routes
	$(ARTISAN) route:list --except-vendor

logs: ## Tail Laravel log file
	tail -f storage/logs/laravel.log

## ── IEEPIS Custom Commands ────────────────────────────────────────────────
qrcodes: ## Generate QR codes for all equipment
	$(ARTISAN) ieepis:generate-qrcodes

export: ## Export inventory to Excel
	$(ARTISAN) ieepis:export-inventory

warranties: ## Check expiring warranties (< 90 days)
	$(ARTISAN) ieepis:check-warranties

## ── Filament ──────────────────────────────────────────────────────────────
filament-admin: ## Create a new Filament admin user
	$(ARTISAN) make:filament-user

filament-optimize: ## Optimize Filament for production
	$(ARTISAN) filament:optimize

## ── Composer ──────────────────────────────────────────────────────────────
install: ## Install PHP dependencies
	$(SAIL) composer install

update: ## Update PHP dependencies
	$(SAIL) composer update

dump: ## Regenerate autoloader
	$(SAIL) composer dump-autoload
