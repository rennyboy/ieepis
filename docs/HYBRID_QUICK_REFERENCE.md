# 🔄 Hybrid Setup - Quick Reference

## What is Hybrid Setup?

```
Local Machine          Docker Containers
├─ Laravel App         ├─ MySQL Database
├─ PHP Code            ├─ Redis Cache
├─ Node/npm            ├─ Mailpit (Email testing)
└─ Composer            └─ Optional Nginx
```

---

## ONE-TIME SETUP (First Time)

```bash
# 1. Install dependencies locally
composer install
npm install

# 2. Create .env file
cp .env.example .env

# 3. Generate APP_KEY
php artisan key:generate

# 4. Start Docker services
docker compose -f docker-compose.hybrid.yml up -d

# 5. Run migrations (connects to Docker DB)
php artisan migrate

# 6. Create test user (optional)
php artisan tinker
>>> App\Models\User::create(['name' => 'Test', 'email' => 'test@local', 'password' => Hash::make('password')])
>>> exit

# 7. Start Laravel locally
php artisan serve

# 8. Visit: http://localhost:8000
```

---

## DAILY WORKFLOW

### Start Work
```bash
# Terminal 1: Start Docker services
docker compose -f docker-compose.hybrid.yml up -d

# Terminal 2: Start Laravel server
php artisan serve
# App is now at http://localhost:8000
```

### Code Normally
- Edit PHP/Blade files in your IDE
- Changes appear instantly on refresh
- No container rebuilds needed

### Stop Work
```bash
# Stop Laravel (Ctrl+C in terminal)
# Stop Docker
docker compose -f docker-compose.hybrid.yml stop
```

---

## REQUIRED .env VALUES

```env
APP_ENV=local
APP_DEBUG=true
APP_KEY=base64:...  # from php artisan key:generate

# Database (Docker MySQL on your machine)
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ieepis_db
DB_USERNAME=ieepis_user
DB_PASSWORD=ieepis_password

# Cache (Docker Redis on your machine)
CACHE_HOST=127.0.0.1
CACHE_PORT=6379
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Session & Queue
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail (Docker Mailpit)
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@ieepis.local"
```

---

## DOCKER SERVICES COMMANDS

| Task | Command |
|------|---------|
| **Start** | `docker compose -f docker-compose.hybrid.yml up -d` |
| **Stop** | `docker compose -f docker-compose.hybrid.yml stop` |
| **Status** | `docker compose -f docker-compose.hybrid.yml ps` |
| **Logs** | `docker compose -f docker-compose.hybrid.yml logs -f db` |
| **Reset** | `docker compose -f docker-compose.hybrid.yml down -v` |

---

## LOCAL LARAVEL COMMANDS

| Task | Command |
|------|---------|
| **Start Server** | `php artisan serve` |
| **Migrate** | `php artisan migrate` |
| **Fresh** | `php artisan migrate:fresh --seed` |
| **Tinker** | `php artisan tinker` |
| **Tests** | `php artisan test` |
| **Cache Clear** | `php artisan cache:clear` |
| **Frontend Build** | `npm run build` |
| **Frontend Dev** | `npm run dev` |
| **Mail UI** | http://localhost:8025 |

---

## DATABASE ACCESS

```bash
# MySQL Command Line
mysql -h 127.0.0.1 -u ieepis_user -p ieepis_db
# Password: ieepis_password

# Via Tinker
php artisan tinker
>>> DB::table('users')->all();
>>> exit
```

---

## REDIS ACCESS

```bash
# Redis CLI
redis-cli -h 127.0.0.1

# Via Tinker
php artisan tinker
>>> Cache::put('key', 'value', 60);
>>> Cache::get('key');
>>> exit
```

---

## TROUBLESHOOTING

| Problem | Solution |
|---------|----------|
| Database won't connect | `docker compose -f docker-compose.hybrid.yml restart db` |
| Redis won't connect | `docker compose -f docker-compose.hybrid.yml restart redis` |
| Port 8000 in use | `php artisan serve --port=8001` |
| Permission error | `php artisan cache:clear` |
| Fresh database | `php artisan migrate:fresh --seed` |

---

## USEFUL SHORTCUTS (Optional)

Add to `.bashrc` or `.zshrc`:

```bash
alias dc-up='docker compose -f docker-compose.hybrid.yml up -d'
alias dc-down='docker compose -f docker-compose.hybrid.yml stop'
alias dc-ps='docker compose -f docker-compose.hybrid.yml ps'
alias dc-logs='docker compose -f docker-compose.hybrid.yml logs -f'
alias dc-migrate='php artisan migrate'
alias dc-fresh='php artisan migrate:fresh --seed'
alias dc-tinker='php artisan tinker'
alias dc-test='php artisan test'
```

Then use:
```bash
dc-up          # Start services
dc-migrate     # Run migrations
dc-tinker      # Open Tinker
dc-test        # Run tests
```

---

## KEY DIFFERENCES FROM FULL DOCKER

| Aspect | Full Docker | Hybrid |
|--------|-------------|--------|
| **Laravel** | Container | Local |
| **Code Changes** | Rebuild needed | Instant |
| **Debugging** | Hard | Easy (use IDE) |
| **Speed** | Slower | Faster |
| **Development** | Production-like | Traditional |

---

## VERIFY SETUP WORKS

```bash
# Test database
php artisan tinker
>>> DB::connection()->getPdo()
>>> exit

# Test Redis
redis-cli -h 127.0.0.1 PING
# Should return: PONG

# Test web
curl http://localhost:8000/health
# Should return: healthy
```

---

## FILES TO KNOW

| File | Purpose |
|------|---------|
| `docker-compose.hybrid.yml` | Services config (MySQL, Redis) |
| `.env` | Your local configuration |
| `php.ini` | PHP config (if customizing) |
| Your code | Runs locally (not in container) |

---

## DOCUMENTATION FILES

- **HYBRID_SETUP_GUIDE.md** - Complete setup walkthrough
- **HYBRID_QUICK_REFERENCE.md** - This file
- **DOCKER_QUICK_REFERENCE.md** - All Docker commands

---

## NEXT STEPS

1. Follow one-time setup above
2. Verify everything works
3. Start developing
4. When done, stop services with `docker compose -f docker-compose.hybrid.yml stop`

---

## PRODUCTION DEPLOYMENT

When ready to deploy:
- **Namecheap cPanel**: See `NAMECHEAP_CPANEL_DEPLOYMENT.md`
- **Render**: See `RENDER_START_HERE.md`

---

**Status:** ✅ Hybrid setup ready
**Laravel:** Local machine (php artisan serve)
**Database:** Docker MySQL (localhost:3306)
**Cache:** Docker Redis (localhost:6379)
**Perfect for:** Fast local development!