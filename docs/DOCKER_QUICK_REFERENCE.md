# 🐳 Docker Quick Reference - IEEPIS

## One-Time Setup

```bash
# Copy environment file
cp .env.example .env

# Build images (first time)
docker compose -f docker-compose.local.yml build

# Start containers
docker compose -f docker-compose.local.yml up -d

# Run migrations
docker compose -f docker-compose.local.yml exec app php artisan migrate
```

---

## Daily Commands

### Start & Stop

```bash
# Start containers
docker compose -f docker-compose.local.yml up -d

# Stop containers
docker compose -f docker-compose.local.yml stop

# Restart containers
docker compose -f docker-compose.local.yml restart

# View status
docker compose -f docker-compose.local.yml ps

# Stop and remove everything
docker compose -f docker-compose.local.yml down
```

### View Logs

```bash
# All containers
docker compose -f docker-compose.local.yml logs -f

# Just app
docker compose -f docker-compose.local.yml logs -f app

# Just database
docker compose -f docker-compose.local.yml logs -f db

# Just Redis
docker compose -f docker-compose.local.yml logs -f redis

# Without following (one-time view)
docker compose -f docker-compose.local.yml logs app
```

---

## Laravel Commands

### Run Artisan Commands

```bash
# Generic format
docker compose -f docker-compose.local.yml exec app php artisan [command]

# Examples
docker compose -f docker-compose.local.yml exec app php artisan migrate
docker compose -f docker-compose.local.yml exec app php artisan migrate:rollback
docker compose -f docker-compose.local.yml exec app php artisan migrate:fresh
docker compose -f docker-compose.local.yml exec app php artisan db:seed
docker compose -f docker-compose.local.yml exec app php artisan cache:clear
docker compose -f docker-compose.local.yml exec app php artisan config:clear
docker compose -f docker-compose.local.yml exec app php artisan route:list
docker compose -f docker-compose.local.yml exec app php artisan tinker
docker compose -f docker-compose.local.yml exec app php artisan test
```

### Run Tests

```bash
# All tests
docker compose -f docker-compose.local.yml exec app php artisan test

# Specific test file
docker compose -f docker-compose.local.yml exec app php artisan test tests/Feature/ExampleTest.php

# With coverage
docker compose -f docker-compose.local.yml exec app php artisan test --coverage

# Filter by name
docker compose -f docker-compose.local.yml exec app php artisan test --filter=testName
```

### Build Frontend

```bash
# Install dependencies
docker compose -f docker-compose.local.yml exec app npm install

# Development mode (hot reload)
docker compose -f docker-compose.local.yml exec app npm run dev

# Production build
docker compose -f docker-compose.local.yml exec app npm run build
```

---

## Interactive Shells

### Bash in App Container

```bash
docker compose -f docker-compose.local.yml exec app bash

# Then inside:
php artisan migrate
composer install
npm run build
exit
```

### MySQL Command Line

```bash
docker compose -f docker-compose.local.yml exec db mysql -u ieepis_user -p ieepis_db

# Password: ieepis_password

# Then in MySQL:
SHOW TABLES;
SELECT * FROM users;
DESC users;
QUIT;
```

### Redis CLI

```bash
docker compose -f docker-compose.local.yml exec redis redis-cli

# Then in Redis:
PING
SET key value
GET key
FLUSHALL
EXIT
```

### PHP Tinker (REPL)

```bash
docker compose -f docker-compose.local.yml exec app php artisan tinker

# Then in Tinker:
>>> DB::connection()->getPdo()
>>> Cache::put('key', 'value')
>>> App\Models\User::all()
>>> exit
```

---

## Database Commands

### Migrations

```bash
# Run all pending migrations
docker compose -f docker-compose.local.yml exec app php artisan migrate

# Rollback last batch
docker compose -f docker-compose.local.yml exec app php artisan migrate:rollback

# Rollback all
docker compose -f docker-compose.local.yml exec app php artisan migrate:reset

# Fresh migration (careful - deletes all data!)
docker compose -f docker-compose.local.yml exec app php artisan migrate:fresh

# Fresh with seed
docker compose -f docker-compose.local.yml exec app php artisan migrate:fresh --seed

# Check migration status
docker compose -f docker-compose.local.yml exec app php artisan migrate:status
```

### Seeding

```bash
# Run all seeders
docker compose -f docker-compose.local.yml exec app php artisan db:seed

# Run specific seeder
docker compose -f docker-compose.local.yml exec app php artisan db:seed --class=UserSeeder

# Seed on fresh migration
docker compose -f docker-compose.local.yml exec app php artisan migrate:fresh --seed
```

### Backup & Restore

```bash
# Export database
docker compose -f docker-compose.local.yml exec db mysqldump -u ieepis_user -p ieepis_db > backup.sql
# Password: ieepis_password

# Import database
docker compose -f docker-compose.local.yml exec -T db mysql -u ieepis_user -p ieepis_db < backup.sql
# Password: ieepis_password
```

---

## Troubleshooting

### Check Service Health

```bash
# View all containers and status
docker compose -f docker-compose.local.yml ps

# Check logs for errors
docker compose -f docker-compose.local.yml logs app
docker compose -f docker-compose.local.yml logs db
docker compose -f docker-compose.local.yml logs redis
```

### Fix Common Issues

```bash
# Port already in use? Change in docker-compose.local.yml ports section

# Database won't start? Check logs and restart
docker compose -f docker-compose.local.yml logs db
docker compose -f docker-compose.local.yml restart db
sleep 10

# Permission errors on storage?
docker compose -f docker-compose.local.yml exec app chmod -R 777 storage bootstrap/cache

# Container exits immediately?
docker compose -f docker-compose.local.yml logs app  # View error

# Need to rebuild?
docker compose -f docker-compose.local.yml build --no-cache
docker compose -f docker-compose.local.yml up -d
```

### Full Reset

```bash
# Remove all containers and data
docker compose -f docker-compose.local.yml down -v

# Then setup again
docker compose -f docker-compose.local.yml build
docker compose -f docker-compose.local.yml up -d
docker compose -f docker-compose.local.yml exec app php artisan migrate
```

---

## Composer Commands

```bash
# Install dependencies
docker compose -f docker-compose.local.yml exec app composer install

# Update dependencies
docker compose -f docker-compose.local.yml exec app composer update

# Install single package
docker compose -f docker-compose.local.yml exec app composer require vendor/package

# Remove package
docker compose -f docker-compose.local.yml exec app composer remove vendor/package

# Dump autoload
docker compose -f docker-compose.local.yml exec app composer dump-autoload
```

---

## Docker System Commands

```bash
# View all images
docker images

# View all containers (running and stopped)
docker ps -a

# Remove unused images
docker image prune

# Remove unused volumes
docker volume prune

# Remove unused networks
docker network prune

# Full cleanup (aggressive)
docker system prune -a --volumes

# View disk usage
docker system df
```

---

## Environment & Config

### Edit .env

```bash
# View current .env
cat .env

# Edit .env
nano .env
# Or use your editor

# Reload config after changes
docker compose -f docker-compose.local.yml exec app php artisan config:clear
docker compose -f docker-compose.local.yml exec app php artisan cache:clear
```

### Important .env Values

```env
APP_ENV=local           # local, development, production
APP_DEBUG=true          # true for debugging, false in production
APP_URL=http://localhost:8080

DB_HOST=db             # Docker service name
DB_DATABASE=ieepis_db
DB_USERNAME=ieepis_user
DB_PASSWORD=ieepis_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

---

## Shortcuts (Optional)

Add these to your shell profile (`.bashrc`, `.zshrc`):

```bash
alias dc='docker compose -f docker-compose.local.yml'
alias dc-up='docker compose -f docker-compose.local.yml up -d'
alias dc-down='docker compose -f docker-compose.local.yml down'
alias dc-logs='docker compose -f docker-compose.local.yml logs -f'
alias dc-ps='docker compose -f docker-compose.local.yml ps'
alias dc-bash='docker compose -f docker-compose.local.yml exec app bash'
alias dc-artisan='docker compose -f docker-compose.local.yml exec app php artisan'
alias dc-tinker='docker compose -f docker-compose.local.yml exec app php artisan tinker'
alias dc-test='docker compose -f docker-compose.local.yml exec app php artisan test'
alias dc-migrate='docker compose -f docker-compose.local.yml exec app php artisan migrate'
```

Then use:
```bash
dc-up
dc-logs
dc-artisan tinker
dc-test
```

---

## Service Details

| Service | Container | Port | Command |
|---------|-----------|------|---------|
| **App** | ieepis-app | 8080 | `docker compose -f docker-compose.local.yml exec app bash` |
| **Database** | ieepis-db | 3306 | `docker compose -f docker-compose.local.yml exec db mysql -u ieepis_user -p` |
| **Redis** | ieepis-redis | 6379 | `docker compose -f docker-compose.local.yml exec redis redis-cli` |

---

## Quick Start Checklist

- [ ] Docker & Docker Compose installed
- [ ] Run: `cp .env.example .env`
- [ ] Run: `docker compose -f docker-compose.local.yml build`
- [ ] Run: `docker compose -f docker-compose.local.yml up -d`
- [ ] Wait 30 seconds
- [ ] Run: `docker compose -f docker-compose.local.yml exec app php artisan migrate`
- [ ] Visit: http://localhost:8080
- [ ] Admin: http://localhost:8080/admin
- [ ] Start coding! 🚀

---

## Status & Monitoring

```bash
# Real-time resource usage
docker stats

# Container inspection
docker inspect ieepis-app
docker inspect ieepis-db
docker inspect ieepis-redis

# View networks
docker network ls

# View volumes
docker volume ls

# View running processes in container
docker top ieepis-app
```

---

## Performance Tips

```bash
# If slow, check logs for errors
docker compose -f docker-compose.local.yml logs app

# Increase Docker memory (Docker Desktop settings):
# Settings → Resources → Memory: 4-6 GB

# Rebuild after code changes
docker compose -f docker-compose.local.yml build --no-cache

# Restart if weird behavior
docker compose -f docker-compose.local.yml restart

# Check disk usage
docker system df
```

---

## Network Testing

```bash
# Test database from app container
docker compose -f docker-compose.local.yml exec app bash
mysql -h db -u ieepis_user -p ieepis_db

# Test Redis from app container
docker compose -f docker-compose.local.yml exec app bash
redis-cli -h redis

# Test web from host
curl http://localhost:8080
curl http://localhost:8080/health
```

---

**Status:** ✅ Local Docker Ready
**App URL:** http://localhost:8080
**Admin URL:** http://localhost:8080/admin
**Health:** http://localhost:8080/health

Happy coding! 🚀