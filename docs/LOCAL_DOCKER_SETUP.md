# 🐳 Local Docker Setup Guide - Run IEEPIS Locally

## Overview

This guide walks you through running your 4-container Laravel application locally using Docker and Docker Compose.

**Containers:**
- Web Service (Laravel + Nginx + PHP-FPM)
- MySQL Database
- Redis Cache/Sessions/Queue
- Queue Worker (optional)

---

## Prerequisites

### Check You Have These Installed

```bash
# Check Docker
docker --version
# Output should be: Docker version 29.0+

# Check Docker Compose
docker compose version
# Output should be: Docker Compose version 2.0+
```

If either is missing, install from: https://docs.docker.com/get-docker/

### System Requirements
- **Disk Space**: 5+ GB available
- **RAM**: 4+ GB recommended (Docker uses 2-4 GB)
- **CPU**: 2+ cores recommended
- **Network**: Port 8080, 3306, 6379 available (or configure different ports)

---

## Step 1: Prepare Environment File

### 1.1 Create `.env` for Local Development

```bash
cd /path/to/ieepis
cp .env.example .env
```

### 1.2 Update `.env` for Docker

Edit `.env` and ensure these values:

```env
APP_NAME=IEEPIS
APP_ENV=local
APP_DEBUG=true
APP_KEY=base64:HQ9m4lBf0yUVKoqOm2eZ7X1J3L8vKqPoN9Q4R2T5M6U=
APP_URL=http://localhost:8080

# Database (Docker MySQL)
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=ieepis_db
DB_USERNAME=ieepis_user
DB_PASSWORD=ieepis_password

# Cache (Docker Redis)
CACHE_DRIVER=redis
CACHE_HOST=redis
CACHE_PORT=6379

# Session (Redis)
SESSION_DRIVER=redis
SESSION_HOST=redis

# Queue (Redis)
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379

# Mail (Log to console)
MAIL_MAILER=log

# Logging
LOG_CHANNEL=single
LOG_LEVEL=debug
```

---

## Step 2: Build Docker Images

### 2.1 Build the Application Image

```bash
docker compose -f docker-compose.local.yml build
```

**What this does:**
- Reads the `Dockerfile`
- Installs PHP extensions
- Installs Composer dependencies
- Creates the Laravel image

**Expected output:**
```
[+] Building 45.3s (25/25) FINISHED
```

**Takes:** 2-5 minutes (first time only, faster subsequent times)

---

## Step 3: Start All Containers

### 3.1 Start Services

```bash
docker compose -f docker-compose.local.yml up -d
```

**What this does:**
- Starts the web container
- Starts MySQL database
- Starts Redis cache
- Creates network bridge between them
- Runs health checks

**Expected output:**
```
✓ Container ieepis-local-web    Started
✓ Container ieepis-local-db     Started
✓ Container ieepis-local-redis  Started
```

### 3.2 Verify Services Are Running

```bash
docker compose -f docker-compose.local.yml ps
```

**Expected output:**
```
NAME                      COMMAND                  STATUS
ieepis-local-web         "/entrypoint.sh"         Up (healthy)
ieepis-local-db          "docker-entrypoint..."   Up (healthy)
ieepis-local-redis       "redis-server"           Up (healthy)
```

All should show `Up (healthy)` ✅

---

## Step 4: Verify Setup

### 4.1 Check Database Connection

```bash
docker compose -f docker-compose.local.yml exec app php artisan tinker
```

Then in Tinker:
```php
>>> DB::connection()->getPdo()
```

**Expected output:**
```
=> PDOConnection {#...}
```

Exit Tinker: `exit` or `Ctrl+D`

### 4.2 Check Redis Connection

```bash
docker compose -f docker-compose.local.yml exec app php artisan tinker
```

Then in Tinker:
```php
>>> Cache::put('test', 'value', 60)
>>> Cache::get('test')
```

**Expected output:**
```
=> "value"
```

### 4.3 View Application Logs

```bash
docker compose -f docker-compose.local.yml logs app
```

Should show Laravel startup messages.

---

## Step 5: Access Your Application

### 5.1 Open in Browser

Visit: **http://localhost:8080**

You should see your Laravel application home page.

### 5.2 Access Admin Panel

Visit: **http://localhost:8080/admin**

Should see Filament login page.

### 5.3 Test Database Operations

```bash
docker compose -f docker-compose.local.yml exec app php artisan migrate
```

This runs all pending migrations.

---

## Step 6: Create Test User (Optional)

### 6.1 Create Admin User

```bash
docker compose -f docker-compose.local.yml exec app php artisan tinker
```

Then:
```php
>>> $user = App\Models\User::create([
  'name' => 'Test Admin',
  'email' => 'admin@ieepis.local',
  'password' => Hash::make('password')
]);
>>> exit
```

### 6.2 Login

- Email: `admin@ieepis.local`
- Password: `password`

---

## Useful Commands

### View Logs

```bash
# All containers
docker compose -f docker-compose.local.yml logs -f

# Just web app
docker compose -f docker-compose.local.yml logs -f app

# Just database
docker compose -f docker-compose.local.yml logs -f db

# Just Redis
docker compose -f docker-compose.local.yml logs -f redis
```

### Run Artisan Commands

```bash
# Generic command
docker compose -f docker-compose.local.yml exec app php artisan [command]

# Examples
docker compose -f docker-compose.local.yml exec app php artisan migrate
docker compose -f docker-compose.local.yml exec app php artisan db:seed
docker compose -f docker-compose.local.yml exec app php artisan cache:clear
docker compose -f docker-compose.local.yml exec app php artisan tinker
docker compose -f docker-compose.local.yml exec app php artisan route:list
```

### Access Container Shell

```bash
# Laravel app shell
docker compose -f docker-compose.local.yml exec app bash

# Database shell
docker compose -f docker-compose.local.yml exec db bash

# Once inside, you can run commands directly
php artisan migrate
composer install
npm run build
```

### Access Database

```bash
# MySQL command line
docker compose -f docker-compose.local.yml exec db mysql -u ieepis_user -p ieepis_db

# Password: ieepis_password

# Then run SQL queries
SHOW TABLES;
SELECT * FROM users;
```

### Access Redis

```bash
# Redis CLI
docker compose -f docker-compose.local.yml exec redis redis-cli

# Then you can test Redis
PING
SET key value
GET key
FLUSHALL
```

---

## Stop & Clean Up

### Stop All Containers

```bash
docker compose -f docker-compose.local.yml stop
```

Containers stop but data persists.

### Restart Containers

```bash
docker compose -f docker-compose.local.yml start
```

### Remove Everything (Hard Reset)

```bash
docker compose -f docker-compose.local.yml down -v
```

**Warning:** This deletes all data (database, Redis, etc). Use only for testing.

### Remove Only Containers, Keep Data

```bash
docker compose -f docker-compose.local.yml down
```

Data persists in volumes. Next `up -d` restores everything.

---

## Troubleshooting

### Problem: "Port 8080 already in use"

**Solution:** Change the port in `docker-compose.local.yml`:

```yaml
ports:
  - "8081:8080"  # Changed from 8080 to 8081
```

Then visit: http://localhost:8081

### Problem: "Database connection refused"

**Check:**
```bash
# Is database running?
docker compose -f docker-compose.local.yml ps db

# Check database logs
docker compose -f docker-compose.local.yml logs db
```

**Solution:**
```bash
# Restart database
docker compose -f docker-compose.local.yml restart db

# Wait 10 seconds for startup
sleep 10

# Try again
docker compose -f docker-compose.local.yml exec app php artisan migrate
```

### Problem: "Permission denied" on storage/

**Solution:**
```bash
docker compose -f docker-compose.local.yml exec app bash
chmod -R 775 /var/www/storage /var/www/bootstrap/cache
exit
```

### Problem: "Composer: out of memory"

**Solution:** Increase Docker memory allocation or run:

```bash
docker compose -f docker-compose.local.yml exec app php -d memory_limit=512M /usr/bin/composer install
```

### Problem: "Redis connection refused"

**Check Redis is running:**
```bash
docker compose -f docker-compose.local.yml logs redis
```

**Solution:**
```bash
# Restart Redis
docker compose -f docker-compose.local.yml restart redis

# Then try again
docker compose -f docker-compose.local.yml exec app php artisan cache:clear
```

### Problem: "Container exits immediately"

**Check logs:**
```bash
docker compose -f docker-compose.local.yml logs app
```

**Common causes:**
- APP_KEY not set (check .env)
- Database not ready (try restart)
- Port conflict (check port 8080)

**Solution:**
```bash
# Remove and rebuild
docker compose -f docker-compose.local.yml down -v
docker compose -f docker-compose.local.yml build --no-cache
docker compose -f docker-compose.local.yml up -d
```

### Problem: "Out of disk space"

**Clean up Docker:**
```bash
# Remove unused images
docker image prune -a

# Remove unused volumes
docker volume prune

# Remove unused containers
docker container prune

# Full cleanup (aggressive)
docker system prune -a --volumes
```

---

## Development Workflow

### 1. Start Your Day

```bash
docker compose -f docker-compose.local.yml up -d
```

Containers start. Coffee time ☕

### 2. Make Code Changes

Edit PHP/Blade files normally in your editor. Changes sync automatically via volumes.

### 3. Run Migrations After Schema Changes

```bash
docker compose -f docker-compose.local.yml exec app php artisan migrate
```

### 4. Clear Cache After Config Changes

```bash
docker compose -f docker-compose.local.yml exec app php artisan cache:clear
docker compose -f docker-compose.local.yml exec app php artisan config:clear
```

### 5. Run Tests

```bash
docker compose -f docker-compose.local.yml exec app php artisan test
```

### 6. Process Queues (if using Redis queue)

**Option A:** Manually process jobs
```bash
docker compose -f docker-compose.local.yml exec app php artisan queue:work
```

**Option B:** Enable queue worker in docker-compose.local.yml
```yaml
# Uncomment the worker service in docker-compose.local.yml
```

Then:
```bash
docker compose -f docker-compose.local.yml up -d
```

### 7. End Your Day

```bash
docker compose -f docker-compose.local.yml stop
```

Or leave running - containers are lightweight.

---

## Build Frontend Assets

### Use Docker to Build

```bash
docker compose -f docker-compose.local.yml exec app npm install
docker compose -f docker-compose.local.yml exec app npm run build
```

Or if npm is installed locally:

```bash
npm install
npm run dev  # For development with hot reload
npm run build  # For production build
```

---

## Database Management

### Export Database

```bash
docker compose -f docker-compose.local.yml exec db mysqldump -u ieepis_user -p ieepis_db > backup.sql
# Password: ieepis_password
```

### Import Database

```bash
docker compose -f docker-compose.local.yml exec -T db mysql -u ieepis_user -p ieepis_db < backup.sql
# Password: ieepis_password
```

### Reset Database

```bash
docker compose -f docker-compose.local.yml exec app php artisan migrate:fresh --seed
```

---

## Performance Tips

### 1. Increase Docker Memory

If containers feel slow:

**On Mac/Windows:**
- Docker Desktop → Settings → Resources → Memory: Set to 4-6 GB

**On Linux:**
- Usually unlimited, no action needed

### 2. Use Named Volumes Efficiently

Already configured in docker-compose.local.yml

### 3. Exclude node_modules from Mount

Already configured:
```yaml
volumes:
  - /var/www/vendor
  - /var/www/node_modules
```

This prevents slow file syncing.

### 4. Use Redis for Cache in Development

Already configured for local development.

---

## Enable Queue Worker (Optional)

If you use Laravel jobs/queues:

### 1. Uncomment Worker Service

In `docker-compose.local.yml`, uncomment the worker section:

```yaml
worker:
  build:
    context: .
    dockerfile: Dockerfile
  # ... rest of configuration
```

Or create a separate file: `docker-compose.worker.yml`

### 2. Start with Worker

```bash
docker compose -f docker-compose.local.yml up -d
```

Now you have 4 services:
- app (web)
- db (database)
- redis (cache)
- worker (queue processor)

### 3. Test Queue

```bash
# Dispatch a test job
docker compose -f docker-compose.local.yml exec app php artisan tinker
>>> \Illuminate\Support\Facades\Bus::dispatch(new App\Jobs\TestJob());
>>> exit

# Check if worker processed it
docker compose -f docker-compose.local.yml logs worker
```

---

## Testing with Docker

### Run PHPUnit Tests

```bash
docker compose -f docker-compose.local.yml exec app php artisan test
```

### Run Specific Test

```bash
docker compose -f docker-compose.local.yml exec app php artisan test tests/Feature/ExampleTest.php
```

### Run with Coverage

```bash
docker compose -f docker-compose.local.yml exec app php artisan test --coverage
```

---

## Environment Files

### Keep Two Versions

**`.env.example`** → Committed to git (template)
**`.env`** → NOT committed (your local secrets)

### For Team Development

Share `.env.example`:
```
APP_ENV=local
APP_DEBUG=true
DB_HOST=db
DB_DATABASE=ieepis_db
DB_USERNAME=ieepis_user
DB_PASSWORD=ieepis_password
# ...etc
```

Each developer copies to `.env` and uses same values for Docker.

---

## Docker Compose Commands Reference

```bash
# Build images
docker compose -f docker-compose.local.yml build

# Start containers
docker compose -f docker-compose.local.yml up -d

# Stop containers
docker compose -f docker-compose.local.yml stop

# Start stopped containers
docker compose -f docker-compose.local.yml start

# Restart containers
docker compose -f docker-compose.local.yml restart

# View status
docker compose -f docker-compose.local.yml ps

# View logs
docker compose -f docker-compose.local.yml logs -f

# Execute command
docker compose -f docker-compose.local.yml exec app [command]

# Remove containers
docker compose -f docker-compose.local.yml down

# Remove everything including data
docker compose -f docker-compose.local.yml down -v

# Rebuild after Dockerfile changes
docker compose -f docker-compose.local.yml build --no-cache
docker compose -f docker-compose.local.yml up -d
```

---

## Simplify Commands (Optional)

Create aliases in your shell profile (`.bashrc`, `.zshrc`):

```bash
alias dc-up='docker compose -f docker-compose.local.yml up -d'
alias dc-down='docker compose -f docker-compose.local.yml down'
alias dc-logs='docker compose -f docker-compose.local.yml logs -f'
alias dc-ps='docker compose -f docker-compose.local.yml ps'
alias dc-exec='docker compose -f docker-compose.local.yml exec app'
alias dc-artisan='docker compose -f docker-compose.local.yml exec app php artisan'
alias dc-tinker='docker compose -f docker-compose.local.yml exec app php artisan tinker'
```

Then use:
```bash
dc-up
dc-logs
dc-artisan migrate
dc-tinker
```

---

## Quick Reference

| Task | Command |
|------|---------|
| Start | `docker compose -f docker-compose.local.yml up -d` |
| Stop | `docker compose -f docker-compose.local.yml stop` |
| Logs | `docker compose -f docker-compose.local.yml logs -f app` |
| Migrate | `docker compose -f docker-compose.local.yml exec app php artisan migrate` |
| Tinker | `docker compose -f docker-compose.local.yml exec app php artisan tinker` |
| Tests | `docker compose -f docker-compose.local.yml exec app php artisan test` |
| Shell | `docker compose -f docker-compose.local.yml exec app bash` |
| Clean | `docker compose -f docker-compose.local.yml down -v` |

---

## What's Running

After `docker compose up -d`, you have:

### Web App (http://localhost:8080)
- Nginx web server on port 8080
- PHP 8.4 application
- Supervisor managing processes
- Health checks every 30 seconds

### Database (localhost:3306)
- MySQL 8.0
- Database: `ieepis_db`
- User: `ieepis_user`
- Password: `ieepis_password`

### Cache (localhost:6379)
- Redis 7
- Used for:
  - Application cache
  - Session storage
  - Queue jobs
  - Temporary data

### Worker (Optional)
- Processes queue jobs
- Uses Redis queue
- Auto-restarts on failure

---

## Next Steps

1. ✅ **Start containers:** `docker compose -f docker-compose.local.yml up -d`
2. ✅ **Verify setup:** `docker compose -f docker-compose.local.yml ps`
3. ✅ **Visit app:** http://localhost:8080
4. ✅ **Create test user:** Follow Step 6
5. ✅ **Login to admin:** http://localhost:8080/admin
6. ✅ **Make changes:** Edit code normally
7. ✅ **Test everything:** Run `php artisan test`

---

## Troubleshooting Checklist

- [ ] Docker and Docker Compose installed
- [ ] 5+ GB disk space available
- [ ] `.env` file created and configured
- [ ] Ports 8080, 3306, 6379 are free
- [ ] All containers show "healthy"
- [ ] App loads at http://localhost:8080
- [ ] Database connection works
- [ ] Redis connection works
- [ ] Can login to admin panel

---

## Getting Help

Check logs first:
```bash
docker compose -f docker-compose.local.yml logs app
docker compose -f docker-compose.local.yml logs db
docker compose -f docker-compose.local.yml logs redis
```

Most issues are visible in logs!

---

## Summary

You now have a **complete local Docker development environment**:

✅ Laravel app with Nginx + PHP-FPM  
✅ MySQL database  
✅ Redis cache/sessions  
✅ Optional queue worker  
✅ All containers health-checked  
✅ Hot-reload development  
✅ Easy commands  

**Status:** Ready to develop! 🚀
