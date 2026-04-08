# 🔀 Hybrid Setup Guide - Laravel Local + Docker Services

## Overview

This is a **hybrid development setup**:

| Component | Location | Details |
|-----------|----------|---------|
| **Laravel App** | Your Local Machine | Code runs directly, no container |
| **MySQL Database** | Docker Container | Persistent, isolated database |
| **Redis Cache** | Docker Container | Session, cache, queue backend |
| **Mailpit** | Docker Container | Local email testing tool |
| **Nginx (Optional)** | Docker Container | Web server (or use `php artisan serve` locally) |

**Benefits:**
- ✅ Hot reload - code changes appear instantly
- ✅ Easy debugging - use local IDE and debuggers
- ✅ Consistent services - same DB/Redis everywhere
- ✅ Fast development - no container overhead for PHP
- ✅ Production-ready - same setup scales to production

---

## Prerequisites

### 1. Local Machine Requirements

You need to install these **on your machine** (not in Docker):

```bash
# Check what you have
php --version           # PHP 8.4+
composer --version      # Latest
npm --version          # Latest
git --version          # Latest
```

### 2. Install Missing Components

**On macOS (using Homebrew):**
```bash
brew install php@8.4 composer node
brew services start php@8.4
```

**On Ubuntu/Debian:**
```bash
sudo apt-get install php8.4 php8.4-fpm php8.4-mysql php8.4-redis php8.4-zip composer nodejs
sudo systemctl start php8.4-fpm
sudo systemctl enable php8.4-fpm
```

**On Windows:**
- Install PHP 8.4 from https://www.php.net/downloads
- Install Composer from https://getcomposer.org
- Install Node.js from https://nodejs.org
- Use Windows Terminal or WSL2 for best experience

### 3. Docker

Docker runs the **services only** (MySQL, Redis):

```bash
# Check Docker installation
docker --version      # Should be 20.10+
docker compose version # Should be 2.0+
```

Install from: https://docker.com/products/docker-desktop

---

## Step 1: Clone/Setup Your Laravel Project Locally

### 1.1 Navigate to Project
```bash
cd /path/to/ieepis
```

### 1.2 Install Local Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies (if needed)
npm install
```

### 1.3 Create Local Environment File

```bash
cp .env.example .env
```

### 1.4 Generate APP_KEY

```bash
php artisan key:generate
```

---

## Step 2: Configure `.env` for Hybrid Setup

Edit `.env` and set these values:

```env
APP_NAME=IEEPIS
APP_ENV=local
APP_DEBUG=true
APP_KEY=base64:YOUR_KEY_FROM_php_artisan_key:generate
APP_URL=http://localhost:8080

# Database (Docker MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ieepis_db
DB_USERNAME=ieepis_user
DB_PASSWORD=ieepis_password

# Cache (Docker Redis)
CACHE_DRIVER=redis
CACHE_HOST=127.0.0.1
CACHE_PORT=6379

# Session (Docker Redis)
SESSION_DRIVER=redis
SESSION_HOST=127.0.0.1
SESSION_PORT=6379

# Queue (Docker Redis)
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Mail (Docker Mailpit)
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@ieepis.local"

# Logging
LOG_CHANNEL=single
LOG_LEVEL=debug
```

**Key Points:**
- Use `127.0.0.1` (localhost) for hosts (not `db` or `redis` - those are container names)
- Services run on your machine's ports (3306, 6379)
- Database credentials match Docker environment

---

## Step 3: Start Docker Services Only

### 3.1 Start MySQL and Redis

```bash
# Start services in background
docker compose -f docker-compose.hybrid.yml up -d
```

### 3.2 Verify Services are Running

```bash
# Check status
docker compose -f docker-compose.hybrid.yml ps
```

Expected output:
```
NAME              STATUS
ieepis-mysql-hybrid  Up (healthy)
ieepis-redis-hybrid  Up
ieepis-mail-hybrid   Up
```

### 3.3 Verify Database Connection

```bash
# Test MySQL connection
mysql -h 127.0.0.1 -u ieepis_user -p ieepis_db
# Password: ieepis_password

# You should see MySQL prompt
mysql>
# Exit
exit
```

---

## Step 4: Setup Local Laravel Application

### 4.1 Run Migrations

```bash
php artisan migrate
```

Should create all database tables in the Docker MySQL.

### 4.2 Seed Database (Optional)

```bash
php artisan db:seed
```

Or seed specific data:
```bash
php artisan db:seed --class=UserSeeder
```

### 4.3 Create Admin User (Optional)

```bash
php artisan tinker

# In Tinker:
>>> App\Models\User::create([
  'name' => 'Admin User',
  'email' => 'admin@ieepis.local',
  'password' => Hash::make('password')
]);
>>> exit
```

---

## Step 5: Run Laravel Locally

### Option A: Use `php artisan serve` (Simplest)

```bash
# Start Laravel dev server
php artisan serve

# Output:
# Laravel development server started: http://127.0.0.1:8000
```

Then visit: **http://localhost:8000**

**Stop the server:** Press `Ctrl+C`

### Option B: Use Docker Nginx (Optional)

If you want Nginx in Docker to serve your local Laravel:

1. **Uncomment Nginx** in `docker-compose.hybrid.yml`
2. **Start Nginx**:
   ```bash
   docker compose -f docker-compose.hybrid.yml up -d nginx
   ```
3. **Enable PHP-FPM locally**:
   ```bash
   # On macOS with Homebrew
   brew services start php@8.4
   
   # On Linux with systemd
   sudo systemctl start php8.4-fpm
   ```
4. **Access app**: http://localhost:8080

---

## Step 6: Verify Everything Works

### 6.1 Test Database
```bash
php artisan tinker

# In Tinker:
>>> DB::connection()->getPdo()
=> PDOConnection {#...}
>>> exit
```

### 6.2 Test Redis
```bash
php artisan tinker

# In Tinker:
>>> Cache::put('test', 'value', 60)
>>> Cache::get('test')
=> "value"
>>> exit
```

### 6.3 Test Health Endpoint
```bash
# If using php artisan serve
curl http://localhost:8000/health

# If using Nginx
curl http://localhost:8080/health

# Should return: healthy
```

---

## Daily Development Workflow

### Morning: Start Everything

```bash
# 1. Start Docker services
docker compose -f docker-compose.hybrid.yml up -d

# 2. Start local Laravel server
php artisan serve

# Laravel app is now at http://localhost:8000
```

### During Work: Make Code Changes

Edit your PHP/Blade files normally in your editor. Changes appear immediately!

```bash
# Run migrations if you changed database schema
php artisan migrate

# Clear cache if you changed config
php artisan cache:clear

# Build frontend assets if you changed them
npm run build  # or npm run dev for watch mode
```

### Evening: Stop Everything

```bash
# Option 1: Stop Laravel server
# Ctrl+C in the terminal where php artisan serve is running

# Option 2: Stop Docker services
docker compose -f docker-compose.hybrid.yml stop

# Option 3: Keep everything running overnight
# (services use minimal resources)
```

---

## Useful Commands

### Check Docker Services Status
```bash
docker compose -f docker-compose.hybrid.yml ps
```

### View Docker Logs
```bash
# All services
docker compose -f docker-compose.hybrid.yml logs -f

# Just MySQL
docker compose -f docker-compose.hybrid.yml logs -f db

# Just Redis
docker compose -f docker-compose.hybrid.yml logs -f redis
```

### Access MySQL CLI
```bash
mysql -h 127.0.0.1 -u ieepis_user -p ieepis_db
# Password: ieepis_password
```

### Access Redis CLI
```bash
redis-cli -h 127.0.0.1
```

### Database Backup & Restore
```bash
# Backup
mysqldump -h 127.0.0.1 -u ieepis_user -p ieepis_db > backup.sql
# Password: ieepis_password

# Restore
mysql -h 127.0.0.1 -u ieepis_user -p ieepis_db < backup.sql
# Password: ieepis_password
```

### Run Tests
```bash
php artisan test
```

### Build Frontend
```bash
# Install dependencies
npm install

# Watch mode (auto-rebuild on changes)
npm run dev

# Production build
npm run build
```

---

## Troubleshooting

### Problem: "Connection refused" to Database

**Check:**
1. Docker services are running:
   ```bash
   docker compose -f docker-compose.hybrid.yml ps
   ```

2. MySQL is fully started (wait 30 seconds after starting)

3. Port 3306 is correct:
   ```bash
   netstat -an | grep 3306
   ```

**Solution:**
```bash
# Restart MySQL
docker compose -f docker-compose.hybrid.yml restart db
sleep 10
php artisan migrate
```

### Problem: "Connection refused" to Redis

**Check:**
1. Redis is running:
   ```bash
   docker compose -f docker-compose.hybrid.yml ps redis
   ```

2. Port 6379 is correct:
   ```bash
   redis-cli -h 127.0.0.1 ping
   # Should return: PONG
   ```

**Solution:**
```bash
# Restart Redis
docker compose -f docker-compose.hybrid.yml restart redis
sleep 5
php artisan cache:clear
```

### Problem: Port Already in Use

If port 8000 (Laravel) or 8080 (Nginx) is already in use:

**Option A: Use Different Port**
```bash
# Use port 8001 instead
php artisan serve --port=8001
# Visit http://localhost:8001
```

**Option B: Find and Kill Process**
```bash
# Find what's using port 8000
lsof -i :8000

# Kill it (replace PID with actual process ID)
kill -9 PID
```

### Problem: PHP Extensions Missing

**Symptoms:**
```
Call to undefined function mysqli_connect()
```

**Solution:**
```bash
# Check what's installed
php -m | grep mysql
php -m | grep redis

# Install missing extensions
# On macOS
brew install php@8.4-mysql php@8.4-redis

# On Ubuntu
sudo apt-get install php8.4-mysql php8.4-redis
```

### Problem: Nginx Connection to Local PHP-FPM Failed

If using Docker Nginx:

**On macOS/Windows:**
- `host.docker.internal` should work (in hybrid.conf)

**On Linux:**
- Edit `docker-compose.hybrid.yml` and add:
  ```yaml
  nginx:
    extra_hosts:
      - "host.docker.internal:host-gateway"
  ```

**Or use host IP:**
```bash
# Find your local IP
ifconfig | grep "inet " | grep -v 127.0.0.1

# Update docker/nginx/hybrid.conf
# Change: fastcgi_pass host.docker.internal:9000;
# To: fastcgi_pass YOUR_IP:9000;
```

### Problem: Database Queries Slow

**Solutions:**
1. Verify MySQL container has enough resources:
   ```bash
   docker stats ieepis-mysql
   ```

2. Check for slow queries:
   ```bash
   mysql -h 127.0.0.1 -u root -p
   # Password: root
   >>> SHOW PROCESSLIST;
   ```

3. Increase MySQL memory in Docker Desktop settings

---

## Performance Tips

### 1. Enable Query Caching
In `.env`:
```env
CACHE_DRIVER=redis
```

### 2. Use Redis for Sessions
In `.env`:
```env
SESSION_DRIVER=redis
```

### 3. Optimize Database Indexes
```bash
php artisan tinker
>>> Schema::table('users', function (Blueprint $table) {
  $table->index('email');
});
```

### 4. Monitor Resource Usage
```bash
# See what Docker is using
docker stats

# See what PHP is using
top
```

---

## Frontend Development

### Watch Mode (Auto-rebuild on Changes)
```bash
npm run dev
```

Your changes rebuild automatically as you edit files.

### Build for Production
```bash
npm run build
```

Creates optimized production assets.

### Use Tailwind with Watch
```bash
# If using Tailwind CSS
npm run dev
# This watches for changes and rebuilds CSS
```

---

## Testing Locally

### Run All Tests
```bash
php artisan test
```

### Run Specific Test
```bash
php artisan test tests/Feature/UserTest.php
```

### Run with Coverage
```bash
php artisan test --coverage
```

### Run Tests That Match Pattern
```bash
php artisan test --filter=testLogin
```

---

## Environment: Local vs Production

### Local Hybrid Setup
```
Your Machine:
├─ Laravel App (php artisan serve)
├─ PHP Code (local)
├─ Node/npm (local)
└─ Your Editor

Docker:
├─ MySQL Database
├─ Redis Cache
└─ Optional Nginx
```

### Production Setup
When deploying to production:
- **Namecheap cPanel**: Install PHP locally, use cPanel MySQL
- **Render**: Use Docker containers for everything

See deployment guides:
- `NAMECHEAP_CPANEL_DEPLOYMENT.md`
- `RENDER_START_HERE.md`

---

## Database Migrations

### Create Migration
```bash
php artisan make:migration create_posts_table
```

Edit migration in `database/migrations/`, then:

```bash
# Run migration
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Refresh all
php artisan migrate:fresh --seed
```

---

## Debugging with Local IDE

Since Laravel runs locally, you can use full IDE features:

### VS Code
1. Install PHP Intelephense extension
2. Set breakpoints in your code
3. Use XDebug (optional, advanced)

### PhpStorm
1. Configure PHP 8.4 as project interpreter
2. Set breakpoints
3. Use built-in debugger

### Tinker REPL
```bash
php artisan tinker

# Instant PHP testing
>>> User::all()
>>> Cache::get('key')
>>> DB::table('users')->where('email', 'test@example.com')->first()
```

---

## Stop Services

### Stop All Containers
```bash
docker compose -f docker-compose.hybrid.yml stop
```

Data persists! Next time you run `up -d`, data will still be there.

### Stop and Remove Everything
```bash
docker compose -f docker-compose.hybrid.yml down -v
```

**Warning:** This deletes database and Redis data!

### Restart Containers
```bash
docker compose -f docker-compose.hybrid.yml restart
```

---

## Quick Reference

### Start Work
```bash
docker compose -f docker-compose.hybrid.yml up -d
php artisan serve
```

### Stop Work
```bash
# Ctrl+C to stop php artisan serve
# Then:
docker compose -f docker-compose.hybrid.yml stop
```

### Database Commands
```bash
php artisan migrate
php artisan tinker
php artisan db:seed
php artisan migrate:fresh --seed
```

### Cache Commands
```bash
php artisan cache:clear
php artisan cache:forget key
php artisan cache:flush
```

### Testing
```bash
php artisan test
npm run build
```

---

## Hybrid Setup Architecture

```
┌─────────────────────────────────────────────────┐
│           Your Local Machine                    │
├─────────────────────────────────────────────────┤
│                                                 │
│  Your IDE/Terminal                              │
│  ├─ Laravel App Code (app/, config/, routes/)  │
│  ├─ php artisan serve (or Nginx proxy)         │
│  ├─ Node.js / npm                               │
│  └─ Composer                                    │
│                                                 │
│           ↓ Connects to ↓                       │
│                                                 │
├─────────────────────────────────────────────────┤
│      Docker Engine (Containerized)              │
├─────────────────────────────────────────────────┤
│                                                 │
│  ┌─────────────────┐  ┌──────────────────┐    │
│  │  MySQL 8.0      │  │  Redis 7         │    │
│  │  Port: 3306     │  │  Port: 6379      │    │
│  │  Data persists  │  │  Data persists   │    │
│  └─────────────────┘  └──────────────────┘    │
│                                                 │
│  (Optional)                                     │
│  ┌──────────────────────────────────────┐     │
│  │  Nginx (proxies to local PHP-FPM)    │     │
│  │  Port: 8080                          │     │
│  └──────────────────────────────────────┘     │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

## Files Used

| File | Purpose |
|------|---------|
| `docker-compose.hybrid.yml` | Service containers (MySQL, Redis, optional Nginx) |
| `.env` | Local configuration with Docker host addresses |
| Your local code | Laravel app running on your machine |
| `php.ini` | Local PHP config (if needed) |

---

## Common Workflows

### Adding a New Feature
```bash
# 1. Create migration
php artisan make:migration add_feature_to_table

# 2. Edit migration file

# 3. Run migration
php artisan migrate

# 4. Create model/controller
php artisan make:model Feature
php artisan make:controller FeatureController

# 5. Edit code, test
php artisan serve

# 6. Run tests
php artisan test

# 7. Commit
git add .
git commit -m "Add feature"
```

### Debugging Database Issue
```bash
# Check database via Tinker
php artisan tinker
>>> DB::connection()->getPdo()
>>> DB::table('users')->first()

# Or check via MySQL CLI
mysql -h 127.0.0.1 -u ieepis_user -p ieepis_db
mysql> SELECT * FROM users LIMIT 1;
```

### Clearing Cache Issues
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear
```

---

## Summary

Your hybrid setup:

✅ **Laravel App** - Runs locally on your machine  
✅ **MySQL Database** - Runs in Docker (persistent)  
✅ **Redis Cache** - Runs in Docker (persistent)  
✅ **Hot Reload** - Code changes appear instantly  
✅ **Easy Debugging** - Use local IDE and tools  
✅ **Production Ready** - Same setup scales to production  

**Get started:**
```bash
# Start Docker services
docker compose -f docker-compose.hybrid.yml up -d

# Start Laravel
php artisan serve

# Visit http://localhost:8000
```

That's it! Happy coding! 🚀

---

## Next Steps

1. **Set up locally** following this guide
2. **Test everything works** (migrations, database, cache)
3. **Start developing** (code changes are instant!)
4. **When ready to deploy**: Follow production deployment guides
   - Namecheap cPanel: Traditional hosting
   - Render: Docker-based cloud
